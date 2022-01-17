<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderBanEmail extends Mailable
{
    use SerializesModels;

    private string $lang;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $lang)
    {
        $this->lang = $lang;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): OrderBanEmail
    {
        return $this->subject('Много жалоб по заказу')
            ->markdown("emails.{$this->lang}.order_ban")
            ->with(['url' => 'https://www.google.com']);
    }
}
