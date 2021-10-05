<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
        return $this->getUserData($request->user());
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
        $user = User::find($user_id, User::FIELDS_FOR_SHOW);

        if (!$user) throw new ErrorException(__('message.user_not_found'));

        return $this->getUserData($user);
    }

    /**
     * Получить дополнительные данные связанные с пользователем.
     *
     * @param User $user
     * @return JsonResponse
     */
    private function getUserData(User $user): JsonResponse
    {
        # удаляем поля с паролем и токеном
        unset($user->password, $user->api_token);

        $user->load(['city.country']);

        # добавляем кол-во заказов, как Заказчик и как Исполнитель (фрилансер)
        $user->creator_count = Review::getCountReviewsByCreator($user->id);
        $user->freelancer_count = Review::getCountReviewsByFreelancer($user->id);

        # добавляем количество отзывов
        $user->reviews_count = Review::getCountReviews($user->id);

        # получить последний отзыв
        $last_review = Review::getLastReview($user->id);

        # добавляем последние 2 заказа, созданные пользователем
        $last_orders = (new OrderController)->getOrdersByFilter($user, [
            'user_id' => $user->id,
            'show' => 2,
        ])['data'] ?? '';

        # добавляем последние 2 маршрута, созданные пользователем
        $last_routes = (new RouteController)->getRoutesByFilter($user, [
            'user_id' => $user->id,
            'show' => 2,
        ])['data'] ?? '';

        $user = $user->toArray();

        return response()->json([
            'status' => true,
            'result' => null_to_blank(compact('user','last_review', 'last_orders', 'last_routes')),
        ]);
    }

    /**
     * Валидатор для проверки данных пользователя при их обновлении.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator4update(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data,
            [
                'name'     => 'sometimes|string|max:100',
                'surname'  => 'sometimes|string|max:100',
                'city_id'  => 'integer|exists:cities,id',
                'status'   => 'in:active,banned,removed',
                'birthday' => 'date',
                'gender'   => 'nullable|in:male,female,unknown',
                'photo'    => 'nullable|base64_image',
                'resume'   => 'nullable|string|not_phone|censor',
            ]
        );
    }

    /**
     * Обновить данные пользователя (только публичные).
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function updatePublicData(Request $request): JsonResponse
    {
        $data = validateOrExit($this->validator4update($request->only(User::FIELDS_FOR_EDIT)));

        $user = $request->user();

        if ($request->has('remove_photo')) {
            $data['photo'] = null;
        }

        if ($request->filled('photo')) {
            $data['photo'] = $this->saveImage($data['photo'], $user->id);
        }

        if ($request->filled('resume')) {
            $data['resume'] = $this->processResume($data['resume']);
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
            'lang'     => 'required_without:currency|in:' . implode(',', config('app.languages')),
            'currency' => 'required_without:lang|in:' . implode(',', array_keys(config('app.currencies'))),
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
            'old_password' => ['required', function ($attribute, $value, $fail) {
                if (getHashPassword($value) !== request()->user()->password) {
                    return $fail(__('message.old_password_incorrect'));
                }
                return true;
            }],
            'password' => ['required', 'min:6', 'confirmed'],
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
            'phone' => 'required_without:email|phone|unique:users,phone',
            'email' => 'required_without:phone|email|max:30|unique:users,email',
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
            'card_number' => 'required_without:card_name|bankcard',
            'card_name'   => 'required_without:card_number|max:50',
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
    public function verificationUserChanges(string $token): JsonResponse
    {
        $user_change = UserChange::whereToken($token)->first();

        if (!$user_change) throw new ErrorException(__('message.token_incorrect'));

        $user = User::find($user_change->user_id);
        if (!$user) throw new ErrorException(__('message.user_not_found'));

        $data = [];
        foreach($user_change->getAttributes() as $key => $value) {
            if (in_array($key, $user_change->getFillable()) && !is_null($value)) {
                $data[$key] = $value;
            }
        }
        $user->fill($data)->save();

        $user_change->delete();

        return response()->json([
            'status' => true,
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
        $image_original_name = 'photo-original.jpg';
        $image_main_name     = 'photo.jpg';
        $image_thumb_name    = 'photo-thumb.jpg';

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
