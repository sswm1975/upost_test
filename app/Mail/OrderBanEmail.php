<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderBanEmail extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Много жалоб по заказу')
            ->markdown('emails.orderban')
            ->with([
                'url' => 'https://www.google.com',
            ]);
    }
}
