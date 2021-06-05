<?php

/**
 * Convert Null to Blank string.
 *
 * @param array $data
 * @return array
 */
function null_to_blank(array $data = []): array
{
    array_walk_recursive($data, function (&$item) {
        $item = $item === null ? '' : $item;
    });

    return $data;
}

/**
 * Validate a base64 content.
 *
 * @author Ahmed Fathy, https://stackoverflow.com/questions/51419310/validating-base64-image-laravel/52914093#52914093
 * @param string $base64data
 * @param array $allowedMime example ['png', 'jpg', 'jpeg']
 * @param int $maxSize
 * @return bool
 */
function validate_base64(string $base64data, array $allowedMime, int $maxSize, int $maxWidth, int $maxHeight): bool
{
    # strip out data uri scheme information (see RFC 2397)
    if (strpos($base64data, ';base64') !== false) {
        list(, $base64data) = explode(';', $base64data);
        list(, $base64data) = explode(',', $base64data);
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
function cropAlign($image, $cropWidth, $cropHeight, $horizontalAlign = 'center', $verticalAlign = 'middle')
{
    $width = imagesx($image);
    $height = imagesy($image);
    $horizontalAlignPixels = calculatePixelsForAlign($width, $cropWidth, $horizontalAlign);
    $verticalAlignPixels = calculatePixelsForAlign($height, $cropHeight, $verticalAlign);

    return imageCrop($image, [
        'x' => $horizontalAlignPixels[0],
        'y' => $verticalAlignPixels[0],
        'width' => $horizontalAlignPixels[1],
        'height' => $verticalAlignPixels[1]
    ]);
}

/**
 * Сalculate pixels for align (use function cropAlign).
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
        default: return [0, $imageSize];
    }
}

/**
 * Создать рисунок c пропорциональным измененнем сторон.
 *
 * @param GdImage $src
 * @param int     $size
 * @param string  $full_filename
 * @return void
 */
function createResizedImage($src, int $size, string $full_filename)
{
    $width = imagesx($src);
    $height = imagesy($src);
    $aspect_ratio = $height/$width;

    if ($width <= $size) {
        $new_w = $width;
        $new_h = $height;
    } else {
        $new_w = $size;
        $new_h = abs($new_w * $aspect_ratio);
    }

    $img = imagecreatetruecolor($new_w, $new_h);
    imagecopyresized($img, $src,0,0,0,0,$new_w,$new_h,$width, $height);

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
