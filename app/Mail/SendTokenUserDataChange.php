<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendTokenUserDataChange extends Mailable
{
    /**
     * Отправка токена подтверждения при изменении данных пользовательского профиля.
     */

    use SerializesModels;

    private string $token;

    /**
     * Create a new message instance.
     *
     * @param string $token
     */
    public function __construct(string $token = '')
    {
        $this->token = $token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): SendTokenUserDataChange
    {
        return $this->subject('Токен подтверждения смены данных профиля')
            ->markdown('emails.send_token_user_data_change')
            ->with([
                'token' => $this->token,
            ]);
    }
}
