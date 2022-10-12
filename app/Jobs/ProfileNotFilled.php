<?php

namespace App\Jobs;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Задание: Отправить Пользователям уведомление "Профиль не заполнен".
 *
 * Условие: Отбираются действующие пользователи (status=active и role=user), у которых одно из полей 'name', 'surname', 'birthday', 'card_number', 'card_name' пустое.
 *
 * Результат: В таблицу notice добавляются записи.
 */
class ProfileNotFilled implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Наименование лог-файла.
     *
     * @var string
     */
    const LOG_FILE = 'logs/notices/profile_not_filled.log';

    /**
     * Выполнить задание.
     *
     * @return void
     */
    public function handle()
    {
        if (!active_notice_type($notice_type = NoticeType::PROFILE_NOT_FILLED)) return;

        config(['logging.channels.single.path' => storage_path(self::LOG_FILE)]);

        $rows = $this->getData();

        if (empty($count = $rows->count())) {
            Log::channel('single')->info('Нет данных');
            return;
        }

        foreach ($rows as $user_id) {
            Notice::create([
                'notice_type' => $notice_type,
                'user_id'     => $user_id,
                'object_id'   => $user_id,
            ]);
        }

        Log::channel('single')->info(
            sprintf(
                'Всего отправлено уведомлений: %d (user ids = %s)',
                $count,
                $rows->implode(',')
            )
        );
    }

    /**
     * Получить действующих пользователей, у которых не заполнен в профиле не заполнены обязательные поля.
     * Условия:
     * - статус 'active'
     * - роль 'user'
     * - одно из полей пустое: 'name', 'surname', 'birthday', 'card_number', 'card_name'
     *
     * @return \Illuminate\Support\Collection
     */
    private function getData(): \Illuminate\Support\Collection
    {
        return User::withoutAppends()
            ->where([
                'status' => User::STATUS_ACTIVE,
                'role'   => User::ROLE_USER,
            ])
            ->where(function($query) {
                $query->whereNull(['name', 'surname', 'birthday', 'card_number', 'card_name'], 'or');
            })
            ->pluck('id');
    }
}
