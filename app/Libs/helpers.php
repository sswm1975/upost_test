<?php

function test_api_post($url, $credentials = '', $post_data = [])
{
    $curl = curl_init($url);

    if ($credentials) {
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $credentials);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $curl_response = curl_exec($curl);
    $response = json_decode($curl_response);
    curl_close($curl);

    return $response;
}

function test_api_get($url, $credentials = '')
{
    $curl = curl_init($url);

    if ($credentials) {
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $credentials);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $curl_response = curl_exec($curl);
    $response = json_decode($curl_response);
    curl_close($curl);

    return $response;
}

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
 * Convert a multi-dimensional array into a single-dimensional array.
 *
 * @author Sean Cannon, LitmusBox.com | seanc@litmusbox.com
 * @param  array $array The multi-dimensional array.
 * @return array
 */
function array_flatten(array $array) {
    if (!is_array($array)) {
        return false;
    }
    $result = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, array_flatten($value));
        } else {
            $result = array_merge($result, array($key => $value));
        }
    }
    return $result;
}

/**
 * Validate a base64 content.
 *
 * @author Ahmed Fathy, https://stackoverflow.com/questions/51419310/validating-base64-image-laravel/52914093#52914093
 * @param string $base64data
 * @param array $allowedMime example ['png', 'jpg', 'jpeg']
 * @return bool
 */
function validate_base64(string $base64data, array $allowedMime): bool
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

    # guard Against Invalid MimeType
    $allowedMime = array_flatten($allowedMime);

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
