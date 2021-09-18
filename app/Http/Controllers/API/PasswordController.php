<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Exceptions\ValidatorException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Обработка запроса ссылки для сброса пароля.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $credentials = validateOrExit(['user_email' => 'required|email']);

        $status = Password::sendResetLink($credentials);

        return response()->json([
            'status'  => $status === Password::RESET_LINK_SENT,
            'message' => __($status),
        ]);
    }

    /**
     * Сброс и установка нового пароля.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function reset(Request $request): JsonResponse
    {
        $credentials = validateOrExit([
            'token'         => 'required',
            'user_email'    => 'required|email',
            'user_password' => 'required|min:6|confirmed',
        ]);

        $credentials['password'] = $credentials['user_password'];
        unset($credentials['user_password']);

        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill([
                'user_password' => getHashPassword($password)
            ]);

            $user->save();

            event(new PasswordReset($user));
        });

        return response()->json([
            'status'  => $status === Password::PASSWORD_RESET,
            'message' => __($status),
        ]);
    }
}
