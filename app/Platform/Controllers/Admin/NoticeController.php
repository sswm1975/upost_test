<?php

namespace App\Platform\Controllers\Admin;

use App\Models\Notice;
use App\Models\NoticeType;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;

class NoticeController extends AdminController
{
    protected string $title = 'Уведомления';
    protected string $icon = 'fa-bell-o';
        protected array $breadcrumb = [
        ['text' => 'Чаты и уведомления', 'icon' => 'wechat'],
    ];

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Notice);

        if (!request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->disablePagination(false);
        $grid->paginate(20);

        $grid->column('id', 'Код')->sortable();
        $grid->column('user_id', 'Код П.')->setAttributes(['align'=>'right'])->filter()->sortable();
        $grid->column('user.name', 'Пользователь');
        $grid->column('notice_type', 'Тип')->filter(NoticeType::pluck('name', 'id')->toArray())->sortable();
        $grid->column('type.text_ru', 'Текст уведомления');
        $grid->column('object_id', 'Объект')->setAttributes(['align'=>'right'])->filter();
        $grid->column('data_modal', 'Данные')
            ->modal('Данные', function($model) {
                if (empty($model->data)) return 'Нет данных';

                return '<pre>'. var_export($model->data, true) . '</pre>';
            });
        $grid->column('created_at', 'Создано');
        $grid->column('is_read', 'Прочитано')
            ->bool()
            ->setAttributes(['align'=>'center'])
            ->filter([1 => 'Да', 0 => 'Нет'])
            ->sortable();

        return $grid;
    }
}
