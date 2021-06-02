<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\User;

class ProfileController extends Controller
{
    /**
     * Список полей пользователя для просмотра.
     *
     * @var array
     */
    const FIELDS_FOR_SHOW = [
        'user_id',                   # id
        'user_name',                 # ім’я
        'user_surname',              # прізвище
        'user_location',             # локацію
        'user_register_date',        # дату реєстрації
        'user_last_active',          # час останньої активності
        'user_status',               # статус
        'user_birthday',             # день народження
        'user_gender',               # стать
        'user_photo',                # фото
        'user_resume',               # біографія
        'user_freelancer_rating',    # рейтинг фрілансера
        'user_creator_rating',       # рейтинг виконавця
    ];

    /**
     * Список полей пользователя для редактирования.
     *
     * @var array
     */
    const FIELDS_FOR_EDIT = [
        'user_name',                 # ім'я
        'user_surname',              # прізвище
        'user_city',                 # код міста проживання
        'user_location',             # код міста перебування
        'user_status',               # статус
        'user_birthday',             # дата народження
        'user_gender',               # стать
        'user_photo',                # фото
        'user_resume',               # біографія
    ];

    /**
     * Получить приватные данные пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPrivateData(Request $request): JsonResponse
    {
        if (empty($GLOBALS['user'])) {
            return response()->json([
                'status' => 404,
                'errors' => 'user_not_found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'result' => null_to_blank($GLOBALS['user']->toArray()),
        ]);
    }

    /**
     * Получить публичные данные пользователя.
     *
     * @param  int     $id
     * @param  Request $request
     * @return JsonResponse
     */
    public function getPublicData(int $id, Request $request): JsonResponse
    {
        $user = User::query()
            ->where('user_id', $id)
            ->first(self::FIELDS_FOR_SHOW)
            ->toArray();

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'errors' => 'user_not_found',
            ]);
        }

        return response()->json([
            'status' => 200,
            'result' => null_to_blank($user),
        ]);
    }

    /**
     * Валидатор для проверки данных пользователя при обновлении публичные данных.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data,
            [
                'user_name'     => 'sometimes|string|between:3,100',
                'user_surname'  => 'sometimes|string|between:3,100',
                'user_city'     => 'numeric',
                'user_location' => 'string',
                'user_status'   => 'in:working,new',
                'user_birthday' => 'date',
                'user_gender'   => 'in:Мужской,Женский',
                'user_photo'    => 'nullable|base64_image',
                'user_resume'   => 'nullable|string',
            ],
            [
                'base64_image' => 'not_valid_image',
            ]
        );
    }

    /**
     * Обновить данные пользователя (только публичные данные).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updatePublicData(Request $request): JsonResponse
    {
        $validator = $this->validator($request->only(self::FIELDS_FOR_EDIT));

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $data = $validator->validated();

        $user = $GLOBALS['user'];

        if ($request->has('remove_photo')) {
            $data['user_photo'] = null;
        }

        if (!empty($data['user_photo'])) {
            $data['user_photo'] = $this->saveImage($data['user_photo'], $user->user_id);
        }

        if ($request->filled('user_resume')) {
            $data['user_resume'] = $this->processResume($data['user_resume']);
        }

        $user->update($data);

        return response()->json([
            'status'  => 200,
            'message' => 'profile_updated_successfully',
            'result'  => null_to_blank($data),
        ]);
    }

    /**
     * Сохранить фотографию.
     *
     * @param string $base64_image
     * @param int    $user_id
     * @return string
     */
    protected function saveImage(string $base64_image, int $user_id): string
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

//        $this->createResizedImage($src, 200, $storage_path . $image_main_name);
//        $this->createResizedImage($src, 100, $storage_path . $image_thumb_name);

        imagejpeg(cropAlign($src, 200, 200), $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return $path . $image_main_name;
    }

    /**
     * Создать рисунок c пропорциональным измененнем сторон.
     *
     * @param GdImage $src
     * @param int     $size
     * @param string  $full_filename
     * @return void
     */
    protected function createResizedImage($src, int $size, string $full_filename)
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
     * Обработка биографии пользователя: Удаление всех тегов и атрибутов, кроме разрешенных.
     *
     * @param string $content
     * @return string
     */
    protected function processResume(string $content): string
    {
        return strip_tags(strip_unsafe($content), ['p', 'span', 'b', 'i', 's', 'u', 'strong', 'italic', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
    }
}
