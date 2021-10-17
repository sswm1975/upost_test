<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
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
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function sendResetLinkEmail(Request $request): JsonResponse
    {
        $credentials = validateOrExit(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink($credentials);
        }
        catch (\Exception $e) {
            throw new ErrorException($e->getMessage());
        }

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
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill(['password' => getHashPassword($password)])->save();

            event(new PasswordReset($user));
        });

        return response()->json([
            'status' => $status === Password::PASSWORD_RESET,
            'errors' => [__($status)],
        ]);
    }
}
