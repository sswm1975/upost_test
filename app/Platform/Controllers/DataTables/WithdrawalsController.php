<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Withdrawal;

class WithdrawalsController extends BaseController
{
    protected string $title = 'Заявки на вывод денег';
    protected string $icon = 'fa-money';
    protected string $entity = 'withdrawals';
    protected int $count_columns = 8;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $statuses = [];
        foreach (Withdrawal::STATUSES as $status) {
            $statuses[$status] = __("message.withdrawal.statuses.$status");
        }
        $statuses['all'] =  'Все';

        return compact('statuses');
    }

    /**
     * Получить данные для таблицы.
     *
     * @return array
     */
    public function getData()
    {
        $status = request('status', Withdrawal::STATUS_NEW);

        $data = Withdrawal::with(['user'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'status' => $row->status,
                    'user_id' => $row->user->id,
                    'user_full_name' => $row->user->full_name,
                    'amount' => $row->amount,
                    'email' => $row->email,
                    'created_at' => $row->created_at->format('d.m.Y h:i:s'),
                    'updated_at' => $row->updated_at ? $row->updated_at->format('d.m.Y h:i:s') : '',
                ];
            })
            ->all();

        return compact('data');
    }
}
