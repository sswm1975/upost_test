<?php

use App\Exceptions\ValidatorException;
use App\Models\Dispute;
use App\Models\Tax;
use App\Modules\Calculations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

# Код системного пользователя в таблице users (от этого пользователя отправляются системные сообщения)
const SYSTEM_USER_ID = 0;

# Значения для поля active: 1/0
const VALUE_ACTIVE = 1;
const VALUE_NOT_ACTIVE = 0;
const VALUES_ACTING = [VALUE_ACTIVE => 'Действующие', VALUE_NOT_ACTIVE => 'Не активные'];

/**
 * Convert Null to Blank string.
 *
 * @param array|object $data
 * @return array
 */
function null_to_blank($data = []): array
{
    # конвертация всех объектов в массивы
    $json  = json_encode($data);
    $data = json_decode($json, true);

    # рекурсивно все null-значения меняем на пустую строку
    array_walk_recursive($data, function (&$item) {
        if (is_null($item)) $item = '';
    });

    return $data;
}

/**
 * Получить системное сообщение в зависимости от локали пользователя.
 *
 * @param string $text алиас системного сообщения, пример dispute_in_work:Вася
 * @param int $dispute_id код спора
 * @return string
 */
function system_message(string $text = '', int $dispute_id = 0): string
{
    if (empty($text)) return '';
    $locale = app()->getLocale();

    list($alias, $param) = array_merge(explode(':', $text), ['']);
    $message = config("system_messages.$alias.$locale", $text);

    if ($alias == 'dispute_opened' && $dispute_id) {
        $dispute = Dispute::with(['user:id,name,surname', 'problem'])
            ->withoutAppends()
            ->find($dispute_id, ['user_id', 'problem_id', 'text']);

        $message = str_replace(
            ['dispute_initiator', 'dispute_problem', 'dispute_text'],
            [$dispute->user->short_name, $dispute->problem->name, $dispute->text],
            $message
        );
    }

    if ($alias == 'dispute_in_work') {
        $message = str_replace('manager_name', $param, $message);
    }

    return $message;
}

/**
 * Validate a base64 content.
 *
 * @author Ahmed Fathy, https://stackoverflow.com/questions/51419310/validating-base64-image-laravel/52914093#52914093
 * @param string $base64data
 * @param array $allowedMime example ['png', 'jpg', 'jpeg']
 * @param int $maxSize
 * @param int $maxWidth
 * @param int $maxHeight
 * @return bool
 */
function validate_base64(string $base64data, array $allowedMime, int $maxSize, int $maxWidth, int $maxHeight): bool
{
    # strip out data uri scheme information (see RFC 2397)
    if (strpos($base64data, ';base64') !== false) {
        [, $base64data] = explode(';', $base64data);
        [, $base64data] = explode(',', $base64data);
    }

    # strict mode filters for non-base64 alphabet characters
    if (base64_decode($base64data, true) === false) {
        return false;
    }

    # decoding and then reencoding should not change the data
    if (base64_encode(base64_decode($base64data)) !== $base64data) {
        return false;
    }

    $binaryData = base64_decode($base64data);
    # temporarily store the decoded data on the filesystem to be able to pass it to the fileAdder
    $tmpFile = tempnam(sys_get_temp_dir(), 'medialibrary');
    file_put_contents($tmpFile, $binaryData);

    if (strlen($binaryData) > $maxSize) {
        return false;
    }
    $src = imagecreatefromstring($binaryData);
    $width = imagesx($src);
    $height = imagesy($src);

    if ($width > $maxWidth || $height > $maxHeight) {
        return false;
    }

    # no allowedMimeTypes, then any type would be ok
    if (empty($allowedMime)) {
        return true;
    }

    # Check the MimeTypes
    $validation = Illuminate\Support\Facades\Validator::make(
        ['file' => new Illuminate\Http\File($tmpFile)],
        ['file' => 'mimes:' . implode(',', $allowedMime)]
    );

    return !$validation->fails();
}

/**
 * Convert image to base64 content.
 *
 * @param string $url
 * @return string
 */
function convertImageToBase64(string $url): string
{
    $type = pathinfo($url, PATHINFO_EXTENSION);
    $data = file_get_contents($url);

    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

/**
 * Get Favicon from URL.
 *
 * @param string $url
 * @return string
 */
function getFavicon(string $url): string
{
    $parts = parse_url($url);

    if ($parts === false || empty($parts['host'])) return '';

    $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
    $host = $parts['host'];

    return "https://www.google.com/s2/favicons?domain={$scheme}{$host}";
}

/**
 * Image crop with configurable alignment.
 * (https://stackoverflow.com/posts/49851547/revisions)
 *
 * Here is a native implementation of a function (called cropAlign) that can crop an image to a given width and height with align to the 9 standard points (4 edges, 4 corners, 1 center). *
 * Just pass the image, the desired size of the crop, and the alignment on the two axis (you can use left, center, right or top, middle, bottom irregardless from the axis) for the cropAlign function.*
 *
 * Specification:
 * Description
 *   cropAlign(resource $image, int $width, int $height, string $horizontalAlign = 'center', string $verticalAlign = 'middle')
 * Parameters
 *   image: An image resource, returned by one of the image creation functions, such as imagecreatetruecolor().
 *   width: Width of the final cropped image.
 *   height: Height of the final cropped image.
 *   horizontalAlign: Where the crop should be aligned along the horizontal axis. Possible values are: left/top, center/middle, right/bottom.
 *   verticalAlign: Where the crop should be aligned along the vertical axis. Possible values are: left/top, center/middle, right/bottom.
 * Return Values
 *   Return cropped image resource on success or FALSE on failure. This comes from imagecrop().
 *
 * @param $image
 * @param $cropWidth
 * @param $cropHeight
 * @param string $horizontalAlign
 * @param string $verticalAlign
 * @return false|GdImage|resource
 */
function cropAlign($image, $cropWidth, $cropHeight, string $horizontalAlign = 'center', string $verticalAlign = 'middle')
{
    $width = imagesx($image);
    $height = imagesy($image);
    $horizontalAlignPixels = calculatePixelsForAlign($width, $cropWidth, $horizontalAlign);
    $verticalAlignPixels = calculatePixelsForAlign($height, $cropHeight, $verticalAlign);

    return imageCrop($image, [
        'x'      => $horizontalAlignPixels[0],
        'y'      => $verticalAlignPixels[0],
        'width'  => $horizontalAlignPixels[1],
        'height' => $verticalAlignPixels[1],
    ]);
}

/**
 * Calculate pixels for align (use function cropAlign).
 *
 * @param $imageSize
 * @param $cropSize
 * @param $align
 * @return array
 */
function calculatePixelsForAlign($imageSize, $cropSize, $align): array
{
    switch ($align) {
        case 'left':
        case 'top':
            return [0, min($cropSize, $imageSize)];
        case 'right':
        case 'bottom':
            return [max(0, $imageSize - $cropSize), min($cropSize, $imageSize)];
        case 'center':
        case 'middle':
            return [
                max(0, floor(($imageSize / 2) - ($cropSize / 2))),
                min($cropSize, $imageSize),
            ];
        default:
            return [0, $imageSize];
    }
}

/**
 * Создать рисунок c пропорциональным изменением сторон.
 *
 * @param GdImage $src
 * @param int $size
 * @param string $full_filename
 * @return void
 */
function createResizedImage($src, int $size, string $full_filename)
{
    $width = imagesx($src);
    $height = imagesy($src);
    $aspect_ratio = $height / $width;

    if ($width <= $size) {
        $new_w = $width;
        $new_h = $height;
    }
    else {
        $new_w = $size;
        $new_h = abs($new_w * $aspect_ratio);
    }

    $img = imagecreatetruecolor($new_w, $new_h);
    imagecopyresized($img, $src, 0, 0, 0, 0, $new_w, $new_h, $width, $height);

    imagejpeg($img, $full_filename);
    imagedestroy($img);
}

/**
 * Удаление html-тегов и "опасных" атрибутов.
 *
 * @param string $content
 * @return string
 */
function strip_unsafe(string $content): string
{
    # https://stackoverflow.com/questions/40264465/how-to-correctly-replace-multiple-white-spaces-with-a-single-white-space-in-php/40264711#40264711
    $content = preg_replace("/\s+/u", ' ', preg_replace("/\x{00A0}|\x{000D}|\x{000C}|\x{0085}/u",' ', $content));

    $unsafe = [
        '/<iframe(.*?)<\/iframe>/is',
        '/<title(.*?)<\/title>/is',
        '/<pre(.*?)<\/pre>/is',
        '/<frame(.*?)<\/frame>/is',
        '/<frameset(.*?)<\/frameset>/is',
        '/<object(.*?)<\/object>/is',
        '/<script(.*?)<\/script>/is',
        '/<embed(.*?)<\/embed>/is',
        '/<applet(.*?)<\/applet>/is',
        '/<meta(.*?)>/is',
        '/<!doctype(.*?)>/is',
        '/<link(.*?)>/is',
        '/<body(.*?)>/is',
        '/<\/body>/is',
        '/<head(.*?)>/is',
        '/<\/head>/is',
        '/onload="(.*?)"/is',
        '/onunload="(.*?)"/is',
        '/onchange=["\'](.*?)["\']/is',
        '/onselect=["\'](.*?)["\']/is',
        '/onclick=["\'](.*?)["\']/is',
        '/ondblclick=["\'](.*?)["\']/is',
        '/onkeyup=["\'](.*?)["\']/is',
        '/onkeydown=["\'](.*?)["\']/is',
        '/onkeypress=["\'](.*?)["\']/is',
        '/onmouseover=["\'](.*?)["\']/is',
        '/onmouseenter=["\'](.*?)["\']/is',
        '/onmouseleave=["\'](.*?)["\']/is',
        '/onmousemove=["\'](.*?)["\']/is',
        '/onmousedown=["\'](.*?)["\']/is',
        '/onmouseup=["\'](.*?)["\']/is',
        '/onmouseout=["\'](.*?)["\']/is',
        '/onfocus=["\'](.*?)["\']/is',
        '/onblur=["\'](.*?)["\']/is',
        '/style=["\'](.*?)["\']/is',
        '/<html(.*?)>/is',
        '/<\/html>/is',
        '/<img(.*?)>/is',
        '/<script(.*?)<\/script>/is',
    ];

    return preg_replace($unsafe, "", $content);
}

/**
 * Get SQL.
 * Pre-call DB::enableQueryLog()
 *
 * @param bool $showBindings
 * @return array
 */
function getSQL(bool $showBindings = false): array
{
    $logs =  DB::getQueryLog();

    if (empty($logs) || $showBindings) return $logs;

    return array_map(
        function($log) {
            return preg_replace_array('/\?/', $log['bindings'], $log['query']);
        },
        $logs
    );
}

/**
 * Get format SQL.
 * !Use only if fix Illuminate\Database\Connection and Illuminate\Database\Events\QueryExecuted
 * Pre-call DB::enableQueryLog()
 *
 * @return array
 */
function getSQLForFixDatabase(): array
{
    $logs =  DB::getQueryLog();

    return array_map(
        function($log) {
            return [
                'sql' => preg_replace_array('/\?/', $log['bindings'], $log['query']),
                'time' => $log['time'],
                'rows' => $log['rows'] ?? null,
            ];
        },
        $logs
    );
}

/**
 * Выполнение правил валидации и вызов исключения при ошибках.
 * В исключении возвращается стандартизированный ответ.
 *
 * @param \Illuminate\Contracts\Validation\Validator|array $validator_or_rules
 * @return array
 * @throws ValidationException|ValidatorException
 */
function validateOrExit($validator_or_rules): array
{
    $validator = is_array($validator_or_rules) ? Validator::make(request()->all(), $validator_or_rules) : $validator_or_rules;

    if ($validator->fails()) {
        throw new ValidatorException($validator->errors()->all());
    }

    $data = $validator->validated();

    if (!array_key_exists('user_id', $data) && !empty(request()->user()->id)) {
        $data['user_id'] = request()->user()->id;
    }

    return $data;
}

/**
 * Проверка заполнения профиля (имя, фамилия, дата рождения) у авторизированного пользователя.
 *
 * @return bool
 */
function isProfileNotFilled(): bool
{
    if (!$user = request()->user()) {
        return false;
    };

    return (empty($user->name) || empty($user->surname) || empty($user->birthday));
}

/**
 * Возвращает хэш пароля.
 *
 * @param string $password
 * @return string
 */
function getHashPassword(string $password): string
{
    return md5(md5($password));
}

/**
 * Конвертирование строкового названия валюты в соответствующий символ (знак).
 * Пример: грн => ₴; uah => ₴; usd => $ и т.п.
 * Если символ не определен, то возвращает дефолтный символ $.
 *
 * @param string $currency
 * @return string
 */
function getCurrencySymbol(string $currency): string
{
    $currency = strtolower($currency);

    foreach (config('app.currencies_decode') as $symbol => $masks) {
        if (Str::contains($currency, $masks)) {
            return $symbol;
        }
    }

    return config('app.default_currency');
}

/**
 * Определение наименования валюты по его символу (знаку).
 * Пример: ₴ => uah; $ => usd и т.д.
 *
 * @param string $symbol Символ (знак) валюты (₴, $, ₽, €)
 * @return string
 */
function getCurrencyNameBySymbol(string $symbol): string
{
    return array_search($symbol, config('app.currencies'));
}

/**
 * Возвращает текущий курс для выбранной валюты.
 *
 * @param string $currency Наименование валюты (₴, $, €, ₽)
 * @return mixed
 */
function getCurrencyRate(string $currency)
{
    return config('rates.' . $currency, 1);
}

/**
 * Конвертация суммы выбранной валюты в долларовый эквивалент.
 *
 * @param float  $amount   Сумма
 * @param string $currency Наименование валюты (₴, $, €, ₽)
 * @return float
 */
function convertPriceToUsd(float $amount, string $currency): float
{
    if ($currency == config('app.default_currency')) return $amount;

    $rate = getCurrencyRate($currency);

    return round($amount / $rate, 2);
}

/**
 * Проверить и получить языковую настройку пользователя.
 *
 * @param string $lang
 * @return string
 */
function getLanguage(string $lang):string
{
    if (empty($lang) || !in_array($lang, config('app.languages'))) {
        return config('app.default_language');
    }

    return $lang;
}

/**
 * Проверить и получить пол пользователя.
 *
 * @param string $gender
 * @return string
 */
function getGender(string $gender): string
{
    if (!empty($gender)) {
        if (in_array(mb_strtolower($gender), config('app.males'))) return 'male';
        if (in_array(mb_strtolower($gender), config('app.females'))) return 'female';
    }

    return 'unknown';
}

/**
 * Форматирование даты и времени.
 *
 * @param string $date
 * @param bool $is_datetime
 * @return false|string
 */
function formatDateTime(string $date = '', bool $is_datetime = true)
{
    if (empty($date)) return '';

    return date($is_datetime ? 'd.m.Y H:i:s' : 'd.m.Y', strtotime($date));
}

/**
 * Рассчитать налог.
 *
 * @param string $alias
 * @param float $amount
 * @return int
 */
function calcTax(string $alias = '', float $amount = 0): int
{
    if (empty($alias) || empty($amount)) {
        return 0;
    }

    $code = Tax::whereAlias($alias)->value('code') ?? '';

    return Calculations::runScript($code, $amount);
}

/**
 * Подсветка синтаксиса.
 *
 * @param string $text
 * @return string
 */
function highlightText(string $text): string
{
    $text = trim($text);
    $text = highlight_string($text, true);
    $text = trim($text);
    $text = preg_replace("|^\\<code\\>\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>|", "", $text, 1);  // remove prefix
    $text = preg_replace("|\\</code\\>\$|", "", $text, 1);  // remove suffix 1
    $text = trim($text);  // remove line breaks
    $text = preg_replace("|\\</span\\>\$|", "", $text, 1);  // remove suffix 2
    $text = trim($text);  // remove line breaks
    $text = preg_replace("|^(\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>)(&lt;\\?php)(.*?)(\\</span\\>)(.*?)|", "$5", $text);  // remove custom added "<?php "

    return $text;
}

/**
 * Удобочитаемое форматирование для дальнейшего вывода массивы или объекта.
 *
 * @param $data
 * @param string $empty_data_info
 * @return string
 */
function pretty_print($data, string $empty_data_info = 'Нет данных'): string
{
    if (empty($data)) {
        return "<pre>{$empty_data_info}</pre>";
    }
    $text = str_replace('\\', '', json_encode(json_decode(urldecode($data)), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return "<pre style='text-align:left'>{$text}</pre>";
}

/**
 * Проверка на существование удаленного файла по ссылке.
 *
 * @param string $url
 * @return bool
 */
function remote_file_exists(string $url): bool
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $http_code == 200;
}

/**
 * Возвращает состояние активности уведомления для выбранного типа.
 *
 * @param string $notice_type
 * @return bool
 */
function active_notice_type(string $notice_type): bool
{
    return config("notice_types.{$notice_type}.active", false);
}
