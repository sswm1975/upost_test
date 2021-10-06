<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\ValidatorException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ImageLoaderController extends Controller
{
    /**
     * Загрузить рисунок.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function upload(Request $request):JsonResponse
    {
        $data = validateOrExit([
            'type'   => 'required|in:user,order',
            'image'  => 'required|base64_image',
        ]);

        $method = 'uploadImage4' . Str::title($data['type']);

        if (!method_exists(self::class, $method)) {
            return response()->json([
                'status' => false,
                'errors' => ["Method {$method} not found"],
            ], 500);
        }

        return response()->json([
            'status' => true,
            'image'  => call_user_func([self::class, $method], $data['image'], $request->user()->id),
        ]);
    }

    /**
     * Загрузить фото для профили пользователя.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    public function uploadImage4User(string $base64_image, int $user_id): string
    {
        $path = 'users/' . $user_id . '/';

        $image_original_name = 'user_photo-original.jpg';
        $image_main_name     = 'user_photo.jpg';
        $image_thumb_name    = 'user_photo-thumb.jpg';

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        $src = imagecreatefromstring($image_file);
        if ($src === false) {
            return '';
        }

        imagejpeg(cropAlign($src, 200, 200), $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return $path . $image_main_name;
    }

    /**
     * Загрузить фото для заказа.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    public function uploadImage4Order(string $base64_image, int $user_id): string
    {
        $uniqid = uniqid();

        $path = "content/order/{$user_id}/";

        $image_original_name = "image-original_$uniqid.jpg";
        $image_main_name     = "image_$uniqid.jpg";
        $image_medium_name   = "image-medium_$uniqid.jpg";
        $image_thumb_name    = "image-thumb_$uniqid.jpg";

        $image_file = base64_decode(substr($base64_image, strpos($base64_image, ',') + 1));

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        $src = imagecreatefromstring($image_file);
        if ($src === false) {
            return '';
        }

        createResizedImage($src, 700, $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 400, 400), $storage_path . $image_medium_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return $path . $image_main_name;
    }
}