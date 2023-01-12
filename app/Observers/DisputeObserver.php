<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\Dispute;
use App\Models\Message;

class DisputeObserver
{
    /**
     * Handle the dispute "creating" event.
     *
     * @param Dispute $dispute
     * @return void
     */
    public function creating(Dispute $dispute)
    {
        $dispute->created_at = $dispute->freshTimestamp();
    }

    /**
     * Handle the dispute "created" event.
     *
     * @param Dispute $dispute
     * @return void
     */
    public function created(Dispute $dispute)
    {
        # добавляем события "Спор создан" и "Спор получен"
        $this->addCreatedActions($dispute);

        # информируем в чат об открытии спора
        Message::create([
            'chat_id'    => $dispute->chat_id,
            'user_id'    => $dispute->user_id,
            'dispute_id' => $dispute->id,
            'text'       => 'dispute_opened',
            'images'     => $dispute->images,
        ]);
    }

    /**
     * Handle the dispute "updating" event.
     *
     * @param Dispute $dispute
     * @return void
     */
    public function updating(Dispute $dispute)
    {
        $dispute->updated_at = $dispute->freshTimestamp();

        if ($dispute->status == Dispute::STATUS_CLOSED) {
            $dispute->closed_user_id = request()->user()->id;
        }
    }

    /**
     * Handle the dispute "updated" event.
     *
     * @param Dispute $dispute
     * @return void
     */
    public function updated(Dispute $dispute)
    {
        # изменился статус спора
        if ($dispute->wasChanged(['status'])) {
            $this->addChangeStatusActions($dispute);
        }
    }

    /**
     * Add actions.
     *
     * @param Dispute $dispute
     */
    private function addCreatedActions(Dispute $dispute)
    {
        # событие "Спор создан" для инициатора спора
        Action::create([
            'user_id'  => $dispute->user_id,
            'is_owner' => true,
            'name'     => Action::DISPUTE_CREATED,
            'changed'  => $dispute->getChanges(),
            'data'     => $dispute,
        ]);

        # событие "Получен спор" для ответчика спора
        Action::create([
            'user_id'  => $dispute->respondent_id,
            'is_owner' => false,
            'name'     => Action::DISPUTE_RECEIVED,
            'changed'  => $dispute->getChanges(),
            'data'     => $dispute,
        ]);
    }

    /**
     * Add actions.
     *
     * @param Dispute $dispute
     */
    private function addChangeStatusActions(Dispute $dispute)
    {
        $auth_user_id = request()->user()->id ?? 0;

        # событие "Изменилось состояние спора" для инициатора спора
        Action::create([
            'user_id'  => $dispute->user_id,
            'is_owner' => !empty($auth_user_id) && $auth_user_id == $dispute->user_id,
            'name'     => Action::DISPUTE_STATUS_CHANGED,
            'changed'  => $dispute->getChanges(),
            'data'     => $dispute,
        ]);

        # событие "Изменилось состояние спора" для ответчика спора
        Action::create([
            'user_id'  => $dispute->respondent_id,
            'is_owner' => !empty($auth_user_id) && $auth_user_id == $dispute->respondent_id,
            'name'     => Action::DISPUTE_STATUS_CHANGED,
            'changed'  => $dispute->getChanges(),
            'data'     => $dispute,
        ]);
    }
}
