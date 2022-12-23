<?php

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Канал для чата.
 *
 * Условия доступа:
 * - канал должен существовать;
 * - пользователь является участником чата;
 * - пользователь является админом или модератором.
 */
Broadcast::channel('chat.{chat_id}', function ($user, $chat_id) {
    $chat = Chat::find($chat_id, ['id', 'performer_id', 'customer_id']);

    return isset($chat->id) && (
            in_array($user->id, [$chat->performer_id, $chat->customer_id]) ||
            in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR])
        );
});

/**
 * Канал для пользователя.
 */
Broadcast::channel('user.{user_id}', function ($user, $user_id) {
    return (int) $user->id === (int) $user_id;
});
