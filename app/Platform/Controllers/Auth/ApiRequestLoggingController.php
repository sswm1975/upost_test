<?php

namespace App\Platform\Controllers\Auth;

use App\Models\Log;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Table;

class ApiRequestLoggingController extends AdminController
{
    protected string $title = 'Журнал API-запросов';
    protected string $icon = 'fa-history';
    protected array $breadcrumb = [
        ['text' => 'Админка', 'icon' => 'tasks'],
    ];

    protected function grid(): Grid
    {
        Admin::style('
            .modal-dialog {width:80%}
            .modal-body table.table td + td {white-space: normal;}
        ');
        $grid = new Grid(new Log);

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableBatchActions();

        $grid->tools(function ($tools) {
            $url = route('platform.auth.api_request_logging.truncate');
            $tools->append("<a href='$url' class='btn btn-danger'><i class='fa fa-close'></i>&nbsp;&nbsp;Очистить</a>");
        });

        $grid->quickSearch('url')->placeholder('Поиск...');

        $grid->model()->latest('id');

        $grid->column('id');
        $grid->column('time');
        $grid->column('duration', 'Duration L');
        $grid->column('duration_request', 'Duration F');
        $grid->column('ip');
        $grid->column('method')->filter(['GET', 'POST']);
        $grid->column('url');
        $grid->column('input')->display(function ($values) {
            if (empty($values)) return '';
            $info = '';
            foreach ($values as $key => $value) {
                $info .= "$key = $value<br>";
            }
            return $info;
        });
        $grid->column('server_info')
            ->display(function ($title, $column) {
                if (empty($this->server)) return '';

                return $column->modal('SERVER', function($grid) {
                    return new Table(['№ п/п', 'Файл'], $grid->server);
                });
            })
            ->setAttributes(['align'=>'center']);

        return $grid;
    }

    public function truncateLog()
    {
        Log::query()->truncate();

        return redirect(route('platform.auth.api_request_logging.index'));
    }
}
