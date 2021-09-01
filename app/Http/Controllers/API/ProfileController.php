<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\UserChange;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $user = $request->user();

        # добавляем поле "От даты регистрации прошло Х лет/месяцев/дней"
        $user->user_register_human =  Carbon::parse($user->user_register_date)->diffForHumans();

        # добавляем поле "От даты последней активности прошло Х лет/месяцев/дней"
        $user->user_last_active_human =  Carbon::parse($user->user_last_active)->diffForHumans();

        # формируем ссылку на аватар
        $user->user_photo = $this->linkToUserPhoto($user->user_photo);

        # удаляем поле с паролем
        unset($user->user_password);

        return response()->json([
            'status' => true,
            'result' => null_to_blank($user->toArray()),
        ]);
    }

    /**
     * Получить публичные данные пользователя.
     *
     * @param int $user_id
     * @return JsonResponse
     * @throws ErrorException
     */
    public function getPublicData(int $user_id): JsonResponse
    {
        $user = User::query()
            ->where('user_id', $user_id)
            ->first(User::FIELDS_FOR_SHOW);

        if (!$user) throw new ErrorException(__('message.user_not_found'));

        # добавляем поле "От даты регистрации прошло Х лет/месяцев/дней"
        $user->user_register_human =  Carbon::parse($user->user_register_date)->diffForHumans();

        # добавляем поле "От даты последней активности прошло Х лет/месяцев/дней"
        $user->user_last_active_human =  Carbon::parse($user->user_last_active)->diffForHumans();

        # формируем ссылку на аватар
        $user->user_photo = $this->linkToUserPhoto($user->user_photo);

        return response()->json([
            'status' => true,
            'result' => null_to_blank($user->toArray()),
        ]);
    }

    /**
     * Формирование ссылки на фото пользователя.
     *
     * @param string $photo
     * @return string
     */
    private function linkToUserPhoto($photo = ''): string
    {
         return asset('storage/' . ($photo ?? 'users/no-photo.png'));
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
                'user_status'   => 'in:active,banned,removed',
                'user_birthday' => 'date',
                'user_gender'   => 'nullable|in:Мужской,Женский',
                'user_photo'    => 'nullable|base64_image',
                'user_resume'   => 'nullable|string|not_phone|censor',
            ]
        );
    }

    /**
     * Обновить данные пользователя (только публичные данные).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function updatePublicData(Request $request): JsonResponse
    {
        $data = validateOrExit($this->validator($request->only(User::FIELDS_FOR_EDIT)));

        $user = $request->user();

        if ($request->has('remove_photo')) {
            $data['user_photo'] = null;
        }

        if ($request->filled('user_photo')) {
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
     * @throws ValidationException|ValidatorException
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'user_lang'     => 'required_without:user_currency|in:' . implode(',', config('app.languages')),
            'user_currency' => 'required_without:user_lang|in:' . implode(',', array_keys(config('app.currencies'))),
        ]);

        $request->user()->fill($data)->save();

        return response()->json(['status' => true]);
    }

    /**
     * Обновление пароля пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'old_password'  => ['required', function ($attribute, $value, $fail) {
                if (getHashPassword($value) !== request()->user()->user_password) {
                    return $fail(__('message.old_password_incorrect'));
                }
            }],
            'user_password' => ['required', 'min:6', 'confirmed'],
        ]);

        return response()->json([
            'status' => true,
            'token'  => UserChange::create($data)->token,
        ]);
    }

    /**
     * Обновление емейла и/или телефона пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function updateLogin(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'user_phone'    => ['required_without:user_email', 'phone', 'unique:users,user_phone'],
            'user_email'    => ['required_without:user_phone', 'email', 'max:30', 'unique:users,user_email'],
        ]);

        return response()->json([
            'status' => true,
            'token'  => UserChange::create($data)->token,
        ]);
    }

    /**
     * Обновление данных пластиковой карточки пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function updateCard(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'user_card_number' => ['required_without:user_card_name', 'bankcard'],
            'user_card_name'   => ['required_without:user_card_number', 'max:50'],
        ]);

        return response()->json([
            'status' => true,
            'token'  => UserChange::create($data)->token,
        ]);
    }

    /**
     * Верификация изменения данных пользователя.
     *
     * @param string $token
     * @return JsonResponse
     * @throws ErrorException
     */
    public function verificationUser(string $token): JsonResponse
    {
        $user_change = UserChange::whereToken($token)->first();
        if (!$user_change) throw new ErrorException(__('message.token_incorrect'));

        $user = User::find($user_change->user_id);
        if (!$user) throw new ErrorException(__('message.user_not_found'));

        $data = [];
        foreach($user_change->getAttributes() as $key => $value) {
            if (!is_null($value) && $key != 'user_id' && Str::startsWith($key, 'user_')) {
                $data[$key] = $value;
            }
        }
        $user->fill($data)->save();

        $user_change->delete();

        return response()->json([
            'status' => true
        ]);
    }

    /**
     * Скачать фотографию.
     *
     * @param Request $request
     * @return StreamedResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function downloadImage(Request $request): StreamedResponse
    {
        $data = validateOrExit(['filename' => 'required|string']);

        if (!Storage::disk('public')->exists($data['filename'])) {
            throw new ErrorException(__('message.image_not_found'));
        }

        return Storage::disk('public')->download($data['filename']);
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
