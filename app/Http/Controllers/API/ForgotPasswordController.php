<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Обработка запроса ссылки для сброса пароля.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $credentials = validateOrExit(['user_email' => 'required|email']);

        $status = Password::sendResetLink($credentials);

        return response()->json([
            'status'  => $status === Password::RESET_LINK_SENT,
            'message' => __($status),
        ]);
    }
}
