<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Exceptions\ValidatorException;
use App\Http\Controllers\Controller;
use App\Mail\SendTokenUserDataChange;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
        $user->loadCount(['orders', 'routes']);

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
            'show' => 3,
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
                'status'   => 'in:' . implode(',', User::STATUSES),
                'birthday' => 'date',
                'gender'   => 'nullable|in:' . implode(',', User::GENDERS),
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
            $data['photo'] = (new ImageLoaderController)->uploadImage4User($data['photo'], $user->id);
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
            'currency' => 'required_without:lang|in:' . implode(',', config('app.currencies')),
        ]);

        $request->user()->fill($data)->save();

        return response()->json(['status' => true]);
    }

    /**
     * Отправить код подтверждения с помощью выбранного отправителя для смены логина/пароля/платёжных данных.
     *
     * @param User $user
     * @param array $data
     * @return JsonResponse
     */
    private function sendVerificationCode(User $user, array $data = []): JsonResponse
    {
        $token = UserChange::create($data)->token;

        if ($data['sender'] == 'email') {
            Mail::to($user->email)->send(new SendTokenUserDataChange($token, $user->lang));

            return response()->json([
                'status'  => true,
                'message' => __('message.verification_code.send_by_email') . ' ' . $user->email,
            ]);
        }

        return response()->json([
            'status'  => false,
            'errors' => [__('message.verification_code.send_error')],
        ]);
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
            'password'     => ['required', 'min:6', 'confirmed'],
            'sender'       => 'in:email',
        ]);

        return $this->sendVerificationCode($request->user(), $data);
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
            'phone'  => 'nullable|required_without:email|phone|unique:users,phone',
            'email'  => 'nullable|required_without:phone|email|max:30|unique:users,email',
            'sender' => 'in:email',
        ]);

        return $this->sendVerificationCode($request->user(), $data);
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
            'card_number' => 'nullable|required_without:card_name|bankcard',
            'card_name'   => 'nullable|required_without:card_number|max:50',
            'sender'      => 'in:email',
        ]);

        return $this->sendVerificationCode($request->user(), $data);
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

        if (!$user_change) throw new ErrorException(__('message.verification_code.incorrect'));

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
            'status'  => true,
            'message' => __('message.verification_code.change_successful'),
        ]);
    }

    /**
     * Проверка заполнения профиля (имя, фамилия, дата рождения) у авторизированного пользователя.
     *
     * @return JsonResponse
     */
    public function isProfileFilled(): JsonResponse
    {
        return response()->json(['status' => !isProfileNotFilled()]);
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
