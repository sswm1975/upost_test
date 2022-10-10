<?php

namespace App\Platform\Controllers\Admin;

use App\Platform\Actions\ServiceNotice\SendNotice;
use App\Platform\Extensions\Grid\Actions\Replicate;
use App\Models\ServiceNotice;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class ServiceNoticeController extends AdminController
{
    protected string $title = 'Сервисные уведомления';
    protected string $icon = 'fa-bell';
    protected bool $isCreateButtonRight = true;
    protected bool $enableDropdownAction = true;
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
        $grid = new Grid(new ServiceNotice);

        if (!request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        $grid->quickSearch('name')->placeholder('Поиск...');

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            if (empty($actions->row->sent_at)) {
                $actions->add(new SendNotice);
            } else {
                $actions->disableEdit();
                $actions->add(new Replicate);
            }
        });

        $grid->column('id', 'Код')->sortable();
        $grid->column('name', 'Наименование');
        $grid->column('text', 'Текст уведомления')
            ->display(function () {
                return sprintf('<span class="label label-warning">🇺🇦</span> %s<br><span class="label label-danger">🇷🇺</span> %s<br><span class="label label-primary">🇬🇧</span> %s',
                    $this->text_uk,
                    $this->text_ru,
                    $this->text_en
                );
            });
        $grid->column('user.name', 'Администратор');
        $grid->column('created_at', 'Создано');
        $grid->column('updated_at', 'Изменено');
        $grid->column('sent_at', 'Отправлено');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new ServiceNotice);

        $form->display('id', 'Код');
        $form->text('name', 'Наименование')->required();
        $form->text('text_uk', 'Уведомление 🇺🇦')->required();
        $form->text('text_ru', 'Уведомление 🇷🇺')->required();
        $form->text('text_en', 'Уведомление 🇬🇧')->required();

        return $form;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id): Show
    {
        return $this->showFields(ServiceNotice::findOrFail($id));
    }
}
