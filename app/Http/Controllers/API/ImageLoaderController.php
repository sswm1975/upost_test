<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
            'type'  => 'required|in:user,order,rate,chat,dispute',
            'image' => 'required|base64_image',
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
            'message' => __('message.image_uploaded'),
            'image'  => call_user_func([self::class, $method], $data['image'], $request->user()->id),
        ]);
    }

    /**
     * Загрузить фото для профиля пользователя.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    public function uploadImage4User(string $base64_image, int $user_id): string
    {
        $date = date('YmdHms');
        $uniqid = uniqid();
        $path = "/{$user_id}/user/";
        $image_original_name = "image_original_{$date}_{$uniqid}.jpg";
        $image_main_name     = "image_{$date}_{$uniqid}.jpg";
        $image_thumb_name    = "image_thumb_{$date}_{$uniqid}.jpg";

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        try {
            $src = imagecreatefromstring($image_file);
        } catch (\Exception $ex) {
            return '';
        }

        if ($src === false) {
            return '';
        }

        imagejpeg(cropAlign($src, 200, 200), $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return asset('storage' . $path . $image_main_name);
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
        $date = date('YmdHms');
        $uniqid = uniqid();
        $path = "/{$user_id}/orders/";
        $image_original_name = "image_original_{$date}_{$uniqid}.jpg";
        $image_main_name     = "image_{$date}_{$uniqid}.jpg";
        $image_medium_name   = "image_medium_{$date}_{$uniqid}.jpg";
        $image_thumb_name    = "image_thumb_{$date}_{$uniqid}.jpg";

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        try {
            $src = imagecreatefromstring($image_file);
        } catch (\Exception $ex) {
            return '';
        }

        if ($src === false) {
            return '';
        }

        createResizedImage($src, 700, $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 400, 400), $storage_path . $image_medium_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return asset('storage' . $path . $image_main_name);
    }

    /**
     * Загрузить фото для ставки.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    public function uploadImage4Rate(string $base64_image, int $user_id): string
    {
        $date = date('YmdHms');
        $uniqid = uniqid();
        $path = "/{$user_id}/rates/";
        $image_original_name = "image_original_{$date}_{$uniqid}.jpg";
        $image_main_name     = "image_{$date}_{$uniqid}.jpg";
        $image_thumb_name    = "image_thumb_{$date}_{$uniqid}.jpg";

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        try {
            $src = imagecreatefromstring($image_file);
        } catch (\Exception $ex) {
            return '';
        }

        if ($src === false) {
            return '';
        }

        createResizedImage($src, 700, $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return asset('storage' . $path . $image_main_name);
    }

    /**
     * Загрузить фото для сообщения чата.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    public function uploadImage4Chat(string $base64_image, int $user_id): string
    {
        $date = date('YmdHms');
        $uniqid = uniqid();
        $path = "/{$user_id}/chats/";
        $image_original_name = "image_original_{$date}_{$uniqid}.jpg";
        $image_main_name     = "image_{$date}_{$uniqid}.jpg";
        $image_thumb_name    = "image_thumb_{$date}_{$uniqid}.jpg";

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        try {
            $src = imagecreatefromstring($image_file);
        } catch (\Exception $ex) {
            return '';
        }

        if ($src === false) {
            return '';
        }

        createResizedImage($src, 700, $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return asset('storage' . $path . $image_main_name);
    }

    /**
     * Загрузить фото для сообщения чата.
     *
     * @param string $base64_image
     * @param int $user_id
     * @return string
     */
    public function uploadImage4Dispute(string $base64_image, int $user_id): string
    {
        $date = date('YmdHms');
        $uniqid = uniqid();
        $path = "/{$user_id}/disputes/";
        $image_original_name = "image_original_{$date}_{$uniqid}.jpg";
        $image_main_name     = "image_{$date}_{$uniqid}.jpg";
        $image_thumb_name    = "image_thumb_{$date}_{$uniqid}.jpg";

        $data = substr($base64_image, strpos($base64_image, ',') + 1);
        $image_file = base64_decode($data);

        Storage::disk('public')->put($path . $image_original_name, $image_file);
        $storage_path = Storage::disk('public')->path($path);

        try {
            $src = imagecreatefromstring($image_file);
        } catch (\Exception $ex) {
            return '';
        }

        if ($src === false) {
            return '';
        }

        createResizedImage($src, 700, $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return asset('storage' . $path . $image_main_name);
    }

    /**
     * Удалить изображение.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException|ValidationException
     */
    public function deleteImage(Request $request): JsonResponse
    {
        # параметр url обязателен и должен быть валидной ссылкой
        validateOrExit(['url' => 'required|url']);

        # делим ссылку по строке "storage"
        # к примеру ссылка http://upost.test/storage/1/orders/image_20220718150753_62d556014c833.jpg
        # будет разделена на 2 части: http://upost.test/storage и /1/orders/image_20220718150753_62d556014c833.jpg
        # нам нужна 2-ая часть
        $path = explode('storage', $request->get('url'))[1] ?? '';

        # 2-ой части нет - выходим
        if (empty($path)) {
            return response()->json(['status' => false]);
        }

        # определяем полный путь и маску для удаляемых файлов
        # к примеру для пути /1/orders/image_20220718150753_62d556014c833.jpg
        # на локальном сервере будет такая маска: C:\laragon\www\upost\storage\app/public\/1/orders/image_*20220730220734_62e5831a888d4.jpg"
        $path_mask = Storage::disk('public')->path(str_replace('image_', 'image_*', $path));

        # по маске формируем список файлов для удаления
        $files = File::glob($path_mask);

        # удаляем файлы и возвращаем ответ
        return response()->json([
            'status' => File::delete($files),
        ]);
    }
}
