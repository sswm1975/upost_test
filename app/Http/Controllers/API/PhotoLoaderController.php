<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PhotoLoaderController extends Controller
{
    /**
     * Загрузить фото.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadPhoto(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(),
            [
                'photo_type' => 'required|in:user,order',
                'photo'      => 'required|base64_image',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $method = 'uploadPhoto4' . Str::title($request->get('photo_type'));

        if (!method_exists(self::class, $method)) {
            return response()->json([
                'status' => 500,
                'errors' => "method_{$method}_not_found",
            ]);
        }

        $user = $GLOBALS['user'];

        return response()->json([
            'status' => 200,
            'image' => call_user_func([self::class, $method], $request->get('photo'), $user->user_id),
        ]);
    }

    /**
     * Загрузить фото для профили пользователя.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    protected function uploadPhoto4User(string $base64_image, int $user_id): string
    {
        $path = 'users/' . $user_id . '/';

        $image_original_name = 'user_photo-original.jpg';
        $image_main_name     = 'user_photo.jpg';
        $image_thumb_name    = 'user_photo-thumb.jpg';

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('local')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('local')->path($path);

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
    protected function uploadPhoto4Order(string $base64_image, int $user_id): string
    {
        $uniqid = uniqid();

        $path = "content/order/{$user_id}/";

        $image_original_name = "super_image-original_$uniqid.jpg";
        $image_main_name     = "super_image_$uniqid.jpg";
        $image_medium_name   = "super_image-medium_$uniqid.jpg";
        $image_thumb_name    = "super_image-thumb_$uniqid.jpg";

        $image_file = base64_decode(substr($base64_image, strpos($base64_image, ',') + 1));

        Storage::disk('local')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('local')->path($path);

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