<?php

namespace App\Observers;

use App\Models\Action;
use App\Models\User;
use App\Payments\Stripe;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user)
    {
        $this->addAction($user,Action::USER_REGISTER);

        # в запрос добавляем код пользователя (используется в StripeLog при добавлении новой записи как request()->user()->id)
        request()->merge(['user' =>  $user]);
        request()->setUserResolver(function () use ($user) {
            return $user;
        });

        # регистрируем пользователя в Stripe
        $customer = (new Stripe)->createCustomer([
            'name'  => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        # сохраняем полученный из Stripe код пользователя
        if (isset($customer['id'])) {
            DB::table('users')->where('id', $user->id)->update(['stripe_customer_id' => $customer['id']]);
        }
    }

    /**
     * Handle the user "updated" event.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user)
    {
        # Login & Logout
        if ($user->wasChanged('api_token')) {
            $this->addAction($user,empty($user->api_token) ? Action::USER_LOGOUT : Action::USER_LOGIN);
        }

        # Изменены платежные данные
        if ($user->wasChanged(['card_number', 'card_name'])) {
           $this->addAction($user,Action::USER_CARD_UPDATED);
        }

        # Изменены данные аутентификации
        if ($user->wasChanged(['phone', 'email', 'password'])) {
            $this->addAction($user,Action::USER_AUTH_UPDATED);
        }

        # Изменены основные данные пользователя
        if ($user->wasChanged(User::FIELDS_FOR_EDIT)) {
            $this->addAction($user,Action::USER_PROFILE_UPDATED);
        }

        # Были изменены данные, которые отправляются в Stripe
        if ($user->wasChanged(['phone', 'email', 'name', 'surname'])) {
            (new Stripe)->updateCustomer($user->stripe_customer_id, [
                'name'  => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone
            ]);
        }
    }

    /**
     * Handle the user "deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user)
    {
        $this->addAction($user,Action::USER_DELETED);
    }

    /**
     * Handle the user "restored" event.
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user)
    {
        $this->addAction($user,Action::USER_RESTORED);
    }

    /**
     * Handle the user "force deleted" event.
     *
     * @param User $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        $this->addAction($user,Action::USER_DELETED);
    }

    /**
     * Add action for user's model.
     *
     * @param User $user
     * @param string $name
     */
    private function addAction(User $user, string $name)
    {
        Action::create([
            'user_id'  => $user->id,
            'is_owner' => YES,
            'name'     => $name,
            'changed'  => $user->getChanges(),
            'data'     => Arr::only($user->toArray(), User::FIELDS_FOR_SHOW),
        ]);
    }
}
