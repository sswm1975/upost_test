<?php

namespace App\Platform\Controllers\Auth;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;

class InfoController extends Controller
{
    /**
     * Информация об проекте.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content): Content
    {
        return $content
            ->title('<i class="fa fa-info-circle"></i> Информация')
            ->description('Данные проекта...')
            ->breadcrumb(['text' => 'Админка', 'icon' => 'tasks'], ['text' => 'Информация', 'icon' => 'info-circle'])
            ->row(Dashboard::title())
            ->row(function (Row $row) {
                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
    }
}
