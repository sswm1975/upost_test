<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the payment "creating" event.
     *
     * @param Payment $payment
     * @return void
     */
    public function creating(Payment $payment)
    {
        $payment->created_at = $payment->freshTimestamp();
    }

    /**
     * Handle the payment "created" event.
     *
     * @param Payment $payment
     * @return void
     */
    public function created(Payment $payment)
    {
        # добавляем событие "Платеж создан"
        $this->addAction($payment, Action::PAYMENT_CREATED);
    }

    /**
     * Handle the payment "updating" event.
     *
     * @param Payment $payment
     * @return void
     */
    public function updating(Payment $payment)
    {
        $payment->updated_at = $payment->freshTimestamp();
    }

    /**
     * Handle the payment "updated" event.
     *
     * @param Payment $payment
     * @return void
     */
    public function updated(Payment $payment)
    {
        # добавляем событие "Изменился платеж"
        $this->addAction($payment, Action::PAYMENT_UPDATED);
    }

    /**
     * Add action.
     *
     * @param Payment  $payment
     * @param string $name
     */
    private function addAction(Payment $payment, string $name)
    {
        Action::create([
            'user_id'  => $payment->user_id,
            'is_owner' => true,
            'name'     => $name,
            'changed'  => $payment->getChanges(),
            'data'     => $payment,
        ]);
    }
}
