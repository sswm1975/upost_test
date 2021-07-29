<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /**
     * Сброс и установка нового пароля.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reset(Request $request)
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
            }
        );

        return response()->json([
            'status'  => $status === Password::PASSWORD_RESET,
            'message' => __($status),
        ]);
    }
}
