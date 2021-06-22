<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Получить приватные данные пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPrivateData(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'result' => null_to_blank($request->user()->toArray()),
        ]);
    }

    /**
     * Получить публичные данные пользователя.
     *
     * @param  int $user_id
     * @return JsonResponse
     */
    public function getPublicData(int $user_id): JsonResponse
    {
        $user = User::query()
            ->where('user_id', $user_id)
            ->first(User::FIELDS_FOR_SHOW);

        if (empty($user)) {
            return response()->json([
                'status' => false,
                'errors' => [__('message.user_not_found')],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'result' => null_to_blank($user->toArray()),
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
                'user_name'     => 'sometimes|string|max:100',
                'user_surname'  => 'sometimes|string|max:100',
                'user_city'     => 'integer|exists:city,city_id',
                'user_location' => 'string',
                'user_status'   => 'in:working,new',
                'user_birthday' => 'date',
                'user_gender'   => 'in:Мужской,Женский',
                'user_photo'    => 'nullable|base64_image',
                'user_resume'   => 'nullable|string',
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
        $validator = $this->validator($request->only(User::FIELDS_FOR_EDIT));

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 404);
        }

        $data = $validator->validated();

        $user = $request->user();

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
            'status'  => true,
            'message' => __('message.updated_successful'),
            'result'  => null_to_blank($data),
        ]);
    }

    /**
     * Обновления языка и валюты в профиле пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->only(['user_lang', 'user_currency']),
            [
                'user_lang'     => 'required_without:user_currency|in:' . implode(',', config('app.languages')),
                'user_currency' => 'required_without:user_lang|in:' . implode(',', array_keys(config('app.currencies'))),
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ], 404);
        }

        $user = $request->user();
        $user->fill($validator->validated());
        $user->save();

        return response()->json(['status' => true]);
    }

    /**
     * Обновление пароля пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidatorException
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(),
            [
                'old_password'  => ['required', function ($attribute, $value, $fail) use ($user) {
                    if (md5(md5($value)) !== $user->user_password) {
                        return $fail(__('message.old_password_incorrect'));
                    }
                }],
                'user_password' => ['required', 'min:6', 'confirmed'],
            ]
        );
        validateOrExit($validator);

        $user->user_password = md5(md5($request->get('user_password')));
        $user->save();

        return response()->json(['status' => true]);
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

        imagejpeg(cropAlign($src, 200, 200), $storage_path . $image_main_name);
        imagejpeg(cropAlign($src, 100, 100), $storage_path . $image_thumb_name);

        imagedestroy($src);

        return $path . $image_main_name;
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
