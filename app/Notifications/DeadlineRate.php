<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineRate extends Notification
{
    use Queueable;

    /**
     * Получить каналы доставки уведомления.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Получить представление уведомления в виде письма.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(trans('Close the bet'))
            ->greeting(trans('Greetings!'))
            ->line('<br>')
            ->line(trans("We're worried that you didn't close the bet."))
            ->line(trans("Please close your bid."))
            ->line('<br>')
            ->salutation('<i>' . trans("Regards") . ',<br>' . config('app.name') . '</i>');
    }

    /**
     * Получить представление уведомления в виде массива.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return $notifiable->toArray();
    }
}
