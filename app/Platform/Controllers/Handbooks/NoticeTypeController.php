<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\NoticeType;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class NoticeTypeController extends AdminController
{
    protected string $title = 'Типы уведомлений';
    protected string $icon = 'fa-bell';
    protected bool $isCreateButtonRight = true;
    protected array $breadcrumb = [
        ['text' => 'Справочники', 'icon' => 'book'],
    ];

    const MODES = [
        'scheduler' => 'Планировщик',
        'event' => 'Событие',
        'manually' => 'Вручную',
    ];

    public function menu(): array
    {
        $counts = NoticeType::selectRaw('active, count(1) as total')
            ->groupBy('active')
            ->pluck('total', 'active')
            ->toArray();

        foreach (VALUES_ACTING as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
                'color' => $status ? 'success' : 'danger',
            ];
        }

        return compact('statuses');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        # добавляем в модалке перенос строк
        Admin::style('.modal-body > p {white-space:normal;}');

        $grid = new Grid(new NoticeType);

        $grid->model()->where('active', request('status', VALUE_ACTIVE));

        $grid->quickSearch(['id', 'name'])->placeholder('Поиск...');

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
        });

        $grid->column('id', 'Код')->sortable();
        $grid->column('name', 'Наименование')
            ->modal('Описание', function($model) {
                return !empty($model->description) ? $model->description : 'Нет описания';
            })
            ->sortable();
        $grid->column('mode', 'Режим')->filter(self::MODES);
        $grid->column('text', 'Уведомление')
            ->display(function () {
                return sprintf('<span class="label label-warning">🇺🇦</span> %s<br><span class="label label-danger">🇷🇺</span> %s<br><span class="label label-primary">🇬🇧</span> %s',
                    $this->text_uk,
                    $this->text_ru,
                    $this->text_en
                );
            });
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();
        $grid->column('created_at', 'Создано');
        $grid->column('updated_at', 'Изменено');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new NoticeType);

        $form->text('id', 'Код')
            ->creationRules(['required', "unique:notice_types"])
            ->updateRules(['required', "unique:notice_types,id,{{id}}"]);
        $form->text('name', 'Наименование')->required();
        $form->text('text_uk', 'Уведомление 🇺🇦')->required();
        $form->text('text_ru', 'Уведомление 🇷🇺')->required();
        $form->text('text_en', 'Уведомление 🇬🇧')->required();
        $form->ckeditor('description', 'Описание');
        $form->select('mode', 'Режим')->options(self::MODES)->required();
        $form->switch('active', 'Действует')->default(1)->states(SWITCH_YES_NO);

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
        return $this->showFields(NoticeType::findOrFail($id));
    }
}
