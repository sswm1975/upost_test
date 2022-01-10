<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Exceptions\ValidatorException;
use App\Models\MailFeedback;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class MailController extends Controller
{
    /**
     * Отправить письмо администраторам сайта, сформированное на главной странице формы «У Вас есть Вопросы?».
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|ValidatorException
     */
    public function haveQuestion(Request $request): JsonResponse
    {
        $data = validateOrExit([
            'name'  => 'required|string|max:50',
            'phone' => 'required|phone',
            'email' => 'required|email',
            'text'  => 'required|string|min:5|max:500|not_phone|censor',
        ]);
        $data['subject'] = 'У Вас есть Вопросы?';

        MailFeedback::create($data);

        $body = "Письмо отправлено с главной страницы формы «У Вас есть Вопросы?»\n\n";
        $body .= "Имя: {$data['name']}\nТелефон: {$data['phone']}\nEmail: {$data['email']}\nСообщение: {$data['text']}\n";

        try {
            Mail::raw($body, function($message) {
                $message->to(config('mail.admin_emails'))->subject('Обратная связь: Вопрос от клиента');
            });

            if (!Mail::failures()) {
                return response()->json([
                    'status'  => true,
                    'message' => __('message.email.send_success'),
                ]);
            }
        } catch (Exception $ex) {

        }

        return response()->json([
            'status'  => false,
            'message' => __('message.email.send_error'),
        ]);
    }
}
