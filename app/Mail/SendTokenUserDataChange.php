<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Lang;

class SendTokenUserDataChange extends Mailable
{
    /**
     * Отправка кода подтверждения (токена) при изменении данных пользовательского профиля.
     */

    use SerializesModels;

    private string $token;
    private string $lang;

    /**
     * Create a new message instance.
     *
     * @param string $token
     * @param string $lang
     */
    public function __construct(string $token = '', string $lang = 'en')
    {
        $this->token = $token;
        $this->lang = $lang;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): SendTokenUserDataChange
    {
        $url = rtrim(config('app.wordpress_url'), '/') . '?action=profile_update_verification&token='.$this->token.'&lang='.$this->lang;

        return $this->subject(Lang::get('Profile data change confirmation code'))
            ->markdown("emails.{$this->lang}.send_token_user_data_change")
            ->with(['url' => $url]);
    }
}
