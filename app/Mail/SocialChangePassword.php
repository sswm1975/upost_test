<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SocialChangePassword extends Mailable
{
    /**
     * Отправка письма с дефолтно-установленным паролем при авторизации через соц.сеть, просьба его сменить.
     */

    use Queueable, SerializesModels;

    private array $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): SocialChangePassword
    {
        $lang = $this->data['language'];

        return $this->subject(__('message.email.social_registration', [], $lang))
            ->markdown("emails.{$lang}.social_change_password")
            ->with($this->data);
    }
}
