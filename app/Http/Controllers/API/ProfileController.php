<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                'user_photo'    => 'string',
                'user_resume'   => 'string',
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

        $user = $GLOBALS['user'];
        $result = $user->update($validator->validated());

        return response()->json([
            'status'  => 200,
            'message' => 'profile_updated_successfully',
            'result'  => $result,
        ]);
    }
}
