<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Transaction;

class TransactionObserver
{
    /**
     * Handle the transaction "created" event.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    public function created(Transaction $transaction)
    {
        # добавляем событие "Транзакция создана"
        $this->addAction($transaction, Action::TRANSACTION_CREATED);
    }

    /**
     * Handle the transaction "updated" event.
     *
     * @param  Transaction  $transaction
     * @return void
     */
    public function updated(Transaction $transaction)
    {
        # добавляем событие "Изменилась транзакция"
        $this->addAction($transaction, Action::TRANSACTION_UPDATES);
    }

    /**
     * Add action.
     *
     * @param Transaction $transaction
     * @param string $name
     */
    private function addAction(Transaction $transaction, string $name)
    {
        Action::create([
            'user_id'  => $transaction->user_id,
            'is_owner' => true,
            'name'     => $name,
            'changed'  => $transaction->getChanges(),
            'data'     => $transaction,
        ]);
    }
}
