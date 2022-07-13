<?php

namespace App\Platform\Controllers\Auth;

use App\Models\Constant;
use App\Models\Log;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Table;

class ApiRequestLoggingController extends AdminController
{
    protected string $title = 'Журнал API-запросов';
    protected string $icon = 'fa-wpforms';
    protected array $breadcrumb = [
        ['text' => 'Админка', 'icon' => 'tasks'],
    ];

    const API_REQUEST_LOGGING_ENABLED = 'api_request_logging_enabled';

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
            # Включение/выключение логирования
            $on_off = config(self::API_REQUEST_LOGGING_ENABLED);
            $tools->append(
                sprintf(
                "<a href='%s' class='btn btn-sm btn-%s'><i class='fa fa-toggle-%s'></i><span class='hidden-xs'>&nbsp;&nbsp;%s логирование</span></a>",
                    route('platform.auth.api_request_logging.toggle'),
                    $on_off ? 'warning' : 'success',
                    $on_off ? 'off' : 'on',
                    $on_off ? 'Выключить' : 'Включить'
                )
            );

            # Очистить журнал
            $url = route('platform.auth.api_request_logging.truncate');
            $tools->append("<a href='$url' class='btn btn-sm btn-danger'><i class='fa fa-close'></i><span class='hidden-xs'>&nbsp;&nbsp;Очистить</span></a>");

            # Обновить данные грида
            $tools->append('<a href="javascript:void(0);" class="btn btn-sm btn-info container-refresh"><i class="fa fa-refresh"></i><span class="hidden-xs">&nbsp;&nbsp;Обновить</span></a>');
        });

        $grid->quickSearch('url')->placeholder('Поиск...');

        $grid->model()->latest('id');

        $grid->column('id');
        $grid->column('time');
        $grid->column('duration', 'Duration L')->help('Duration from starting Laravel to sending the response.<br><sub class=\'text-danger\'>ResponseTime - LARAVEL_START</sub>');
        $grid->column('duration_request', 'Duration F')->help('The duration from a WordPress request to sending the response.<br><sub class=\'text-danger\'>ResponseTime - REQUEST_TIME_FLOAT</sub>');
        $grid->column('ip');
        $grid->column('method')->filter(['GET', 'POST']);
        $grid->column('url');
        $grid->column('input')
            ->display(function ($values) {
                if (empty($values)) return '';

                $info = '';
                foreach ($values as $key => $value) {
                    $info .= "$key = $value<br>";
                }

                return $info;
            });
        $grid->column('output_info', 'Output')
            ->display(function ($title, $column) {
                if (empty($this->output)) return '';

                return $column->modal('JSON response', function($grid) {
                    return '<pre style="text-align:left">' . json_encode($grid->output, JSON_PRETTY_PRINT + JSON_UNESCAPED_UNICODE) . '</pre>';
                });
            })
            ->display(function ($modal, $column) {
                $status = ($this->output['status'] ?? false) ? 'fa-check text-green' : 'fa-times text-red';
                return "<i class='fa $status'></i>$modal";
            })
            ->style('text-align:center');
        $grid->column('server_info', 'Server')
            ->display(function ($title, $column) {
                if (empty($this->server)) return '';

                return $column->modal('Server information', function ($grid) {
                    return new Table(['PARAMETER', 'VALUE'], $grid->server);
                });
            })
            ->style('text-align:center');

        return $grid;
    }

    /**
     * Enable/disable logging.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function toggleLog()
    {
        $constant = Constant::firstWhere('name', '=', self::API_REQUEST_LOGGING_ENABLED);
        $constant->value = ! config(self::API_REQUEST_LOGGING_ENABLED);
        $constant->save();

        admin_toastr(
            'Логирование ' . ($constant->value ? 'включено' : 'выключено'),
            $constant->value ? 'success' : 'warning',
            ['positionClass' => 'toast-top-center']
        );

        return redirect(route('platform.auth.api_request_logging.index'));
    }

    /**
     * Truncate log table.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|void
     */
    public function truncateLog()
    {
        Log::query()->truncate();

        admin_toastr($this->title . ' очищен.', 'success', ['positionClass' => 'toast-top-center']);

        return redirect(route('platform.auth.api_request_logging.index'));
    }
}
