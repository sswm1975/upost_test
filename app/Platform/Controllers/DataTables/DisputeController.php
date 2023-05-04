<?php

namespace App\Platform\Controllers\DataTables;

use App\Models\Dispute;

class DisputeController extends BaseController
{
    protected string $title = 'Споры';
    protected string $icon = 'fa-gavel';
    protected string $entity = 'disputes';
    protected int $count_columns = 14;

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $statuses = array_merge(Dispute::STATUSES, ['all' =>  'Все']);

        return compact('statuses');
    }

    /**
     * Получить данные для таблицы.
     *
     * @return array
     */
    public function getData()
    {
        $status = request('status', Dispute::STATUS_ACTIVE);

        # отбираем маршруты
        $data = Dispute::with(['problem', 'user', 'respondent', 'admin_user'])
            ->when($status != 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->get()
            ->map(function ($row) use ($status) {
                return [
                    'id' => $row->id,
                    'status' => $row->status,
                    'problem_id' => $row->problem_id,
                    'problem_name' => $row->problem->name,
                    'problem_days' => $row->problem->days,
                    'manager_id' => $row->admin_user->id ?? '',
                    'manager_full_name' => $row->admin_user->full_name ?? '',
                    'user_id' => $row->user->id,
                    'user_full_name' => $row->user->full_name,
                    'respondent_id' => $row->respondent->id,
                    'respondent_full_name' => $row->respondent->full_name,
                    'deadline' => $row->deadline->format('d.m.Y'),
                    'created_at' => $row->created_at->format('d.m.Y'),
                    'updated_at' => !empty($row->updated_at) ? $row->updated_at->format('d.m.Y') : '',
                ];
            })
            ->all();

        return compact('data');
    }

    private function getActions($id, $status)
    {
        if ($status == Dispute::STATUS_ACTIVE) {
            return <<<EOT
                <div class="grid-dropdown-actions dropdown">
                    <a href="#" style="padding: 0 10px;" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-v"></i>
                    </a>
                    <ul class="dropdown-menu" style="min-width: 70px !important;box-shadow: 0 2px 3px 0 rgba(0,0,0,.2);border-radius:0;left: -65px;top: 5px;">
                        </li><li><a data-_key="$id" href="javascript:void(0);" class="text-green" modal="row-action-modal-appoint">Назначить спор менеджеру</a></li>
                    </ul>
                </div>
EOT;
        }

        if ($status == Dispute::STATUS_APPOINTED) {
            return <<<EOT
                <div class="grid-dropdown-actions dropdown">
                    <a href="#" style="padding: 0 10px;" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-v"></i>
                    </a>
                    <ul class="dropdown-menu" style="min-width: 70px !important;box-shadow: 0 2px 3px 0 rgba(0,0,0,.2);border-radius:0;left: -65px;top: 5px;">
                        </li><li><a data-_key="$id" href="javascript:void(0);" class="text-blue">Взять в работу</a></li>
                    </ul>
                </div>
EOT;
        }

        if ($status == Dispute::STATUS_IN_WORK) {
            return <<<EOT
                <div class="grid-dropdown-actions dropdown">
                    <a href="#" style="padding: 0 10px;" class="dropdown-toggle" data-toggle="dropdown">
                        <i class="fa fa-ellipsis-v"></i>
                    </a>
                    <ul class="dropdown-menu" style="min-width: 70px !important;box-shadow: 0 2px 3px 0 rgba(0,0,0,.2);border-radius:0;left: -65px;top: 5px;">
                        <li><a data-_key="$id" href="javascript:void(0);" class="text-blue" modal="row-action-modal-close1">Закрыть спор (виноват путешественник)</a></li>
                        <li><a data-_key="$id" href="javascript:void(0);" class="text-green" modal="row-action-modal-close2">Закрыть спор (виноват заказчик)</a></li>
                        <li><a data-_key="$id" href="javascript:void(0);" class="text-red">Отменить спор</a></li>
                    </ul>
                </div>
EOT;
        }
        return '';
    }
}
