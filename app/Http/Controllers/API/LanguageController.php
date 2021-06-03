<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LanguageController extends Controller
{
    /**
     * Обновления языка и валюты в профиле пользователя.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = $this->validator($request->only(['lang', 'currency']));

        if ($validator->fails()) {
            return response()->json([
                'status' => 404,
                'errors' => $validator->errors(),
            ]);
        }

        $user = $GLOBALS['user'];

        if ($request->filled('lang')) {
            $user->user_lang = $request->get('lang');
        }

        if ($request->filled('currency')) {
            $user->user_currency = config('app.currencies')[$request->get('currency')];
        }

        $user->save();

        return response()->json([
            'status'  => 200,
        ]);
    }

    /**
     * Валидатор для проверки входных данных.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data,
            [
                'lang'     => 'required_without:currency|in:' . implode(',', config('app.languages')),
                'currency' => 'required_without:lang|in:' . implode(',', array_keys(config('app.currencies'))),
            ],
            [
                'required_without' => 'field_is_empty',
                'in'               => ':attribute_not_exist',
            ]
        );
    }
}
