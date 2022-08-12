<?php

namespace App\Platform\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\OrderBanEmail;
use App\Mail\SendTokenUserDataChange;
use App\Mail\SocialChangePassword;
use App\Models\User;
use App\Notifications\DeadlineRate;
use Encore\Admin\Layout\Content;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Encore\Admin\Facades\Admin;
use Faker\Factory as Faker;

class MailingController extends Controller
{
    const LOCALES = [
        'uk' => 'Uk_UA',
        'ru' => 'Ru_RU',
        'en' => 'GB_GB',
    ];

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        # контент письма: вместо 100% устанавливаем авто-размер, что приводит к выравниванию письма к левому краю.
        Admin::style('.wrapper {width:auto !important;}');

        # получаем список рассылок
        $mailings = static::getMailings();

        # узнаем выбранную рассылку
        $mailing = Arr::last(request()->segments());

        if (!Arr::exists($mailings, $mailing)) {
            return $content->withError('Ошибка', 'Рассылка не найдена!');
        }

        $title = $mailings[$mailing]['title'];
        $icon = $mailings[$mailing]['icon'];
        $lang = request('lang', 'uk');

        return $content
            ->title("<i class='fa {$icon}'></i>&nbsp;{$title}")
            ->description('рассылка')
            ->breadcrumb(
                ['text' => 'Справочники', 'icon' => 'book'],
                ['text' => 'Рассылки', 'icon' => 'envelope-o'],
                ['text' => $mailings[$mailing]['title'], 'icon' => str_replace('fa-', '', $icon)],
            )
            ->row($this->menu($mailing))
            ->body(static::$mailing($lang));
    }

    /**
     * Из пункта меню "Рассылки" получаем список всех подпунктов.
     *
     * @return array
     */
    private static function getMailings(): array
    {
        $menuClass = config('admin.database.menu_model');

        return $menuClass::query()
            ->where('uri', 'like', 'mailings/%')
            ->selectRaw('replace(uri, "mailings/", "") uri, title, icon')
            ->get()
            ->keyBy('uri')
            ->toArray();
    }

    /**
     * Формируем список меню с выбором языка.
     *
     * @param string $mailing
     * @return View
     */
    public function menu(string $mailing = ''): View
    {
        return view('platform.emails.menu')->with([
            'mailing'   => $mailing,
            'languages' => ADMIN_LANGUAGES,
        ]);
    }

    /**
     * Письмо: Смена пароля при регистрации через социальную сеть.
     *
     * @param string $lang
     * @return SocialChangePassword
     */
    private static function social_change_password(string $lang): SocialChangePassword
    {
        $faker = Faker::create(self::LOCALES[$lang]);
        $gender = $faker->randomElement(['male', 'female']);

        return new SocialChangePassword([
            'language'    => $lang,
            'provider'    => $faker->randomElement(['google', 'facebook']),
            'client_name' => $faker->name($gender),
            'email'       => $faker->unique()->email,
            'password'    => Str::random(10),
            'url'         => rtrim(config('app.wordpress_url'), '/') . '/log-in/?change_password',
        ]);
    }

    /**
     * Письмо: Много жалоб по заказу.
     *
     * @param string $lang
     * @return OrderBanEmail
     */
    private static function order_ban(string $lang): OrderBanEmail
    {
        return new OrderBanEmail($lang);
    }

    /**
     * Письмо: Код подтверждения смены данных профиля.
     *
     * @param string $lang
     * @return SendTokenUserDataChange
     */
    private static function send_token_user_data_change(string $lang): SendTokenUserDataChange
    {
        return new SendTokenUserDataChange(Str::random(8), $lang);
    }

    /**
     * Письмо: Уведомление сброса пароля.
     *
     * @param string $lang
     * @return MailMessage
     */
    private static function reset_password(string $lang): MailMessage
    {
        Lang::setLocale($lang);

        $user = User::find(SYSTEM_USER_ID);

        return (new ResetPassword(Str::random(64)))->toMail($user);
    }

    /**
     * Письмо: Закройте ставку, сегодня дата дедлайна.
     *
     * @param string $lang
     * @return MailMessage
     */
    private static function deadline_rate(string $lang): MailMessage
    {
        Lang::setLocale($lang);

        $user = User::find(SYSTEM_USER_ID);

        return (new DeadlineRate(Str::random(64)))->toMail($user);
    }
}
