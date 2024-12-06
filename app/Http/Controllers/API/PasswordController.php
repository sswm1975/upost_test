<?php

namespace App\Http\Controllers\API;

use App\Exceptions\ErrorException;
use App\Http\Controllers\Controller;
use App\Exceptions\ValidatorException;
use App\Models\User;
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

        if (!User::withoutRemoved()->whereEmail($request->get('email'))->exists()) {
            throw new ValidatorException("We can't find a user with that email address.");
        }

        try {
            $status = Password::sendResetLink($credentials);
        }
        catch (\Exception $e) {
            throw new ErrorException($e->getMessage());
        }

        if ($status !== Password::RESET_LINK_SENT) {
            throw new ErrorException(__($status));
        }

        return response()->json([
            'status'  => true,
            'message' => __($status),
        ]);
    }

    /**
     * Сброс и установка нового пароля.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException|ErrorException
     */
    public function reset(Request $request): JsonResponse
    {
        $credentials = validateOrExit([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        unset($credentials['user_id']);

        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill(['password' => $password])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw new ErrorException(__($status));
        }

        return response()->json([
            'status'  => true,
            'message' => __($status),
        ]);
    }
}
