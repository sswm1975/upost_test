<?php

namespace App\Platform\Controllers\Handbooks;

use App\Models\DisputeProblem;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class DisputeProblemController extends AdminController
{
    protected string $title = 'Проблемы спора';
    protected string $icon = 'fa-exclamation-triangle';
    protected bool $isCreateButtonRight = true;
    protected array $breadcrumb = [
        ['text' => 'Справочники', 'icon' => 'book'],
    ];

    const INITIATORS = [
        CUSTOMER  => 'Заказчик',
        PERFORMER => 'Исполнитель',
    ];

    const RATE_STATUSES = [
        'accepted'   => 'Принятая',
        'buyed'      => 'Купленная',
        'successful' => 'Успешная',
    ];

    const ORDER_STATUSES = [
        'accepted'   => 'Доставка началась',
        'buyed'      => 'Товар у путешественника',
        'successful' => 'Заказчик получил товар',
    ];

    public function menu(): array
    {
        $counts = DisputeProblem::selectRaw('initiator, count(1) as total')
            ->groupBy('initiator')
            ->pluck('total', 'initiator')
            ->toArray();

        $statuses = [];
        foreach (self::INITIATORS as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
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
        $grid = new Grid(new DisputeProblem);

        # FILTERS
        $grid->model()->where('initiator', request('status', array_key_first(self::INITIATORS)));

        $grid->selector(function (Grid\Tools\Selector $selector) {
            $selector->selectOne('rate_status', 'СТАТУС', self::ORDER_STATUSES, function ($query, $value) {
                $query->where('rate_status', $value);
            });
        });

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('initiator', 'Инициатор')->replace(self::INITIATORS)->sortable();
        $grid->column('rate_status', 'Ставка')->replace(self::RATE_STATUSES)->sortable();
        $grid->column('text', 'Текст')
            ->display(function () {
                return sprintf('<span class="label label-warning">🇺🇦</span> %s<br><span class="label label-danger">🇷🇺</span> %s<br><span class="label label-primary">🇬🇧</span> %s',
                    $this->name_uk,
                    $this->name_ru,
                    $this->name_en
                );
            });
        $grid->column('days', 'Дней')->help('Количество дней на рассмотрение спора')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new DisputeProblem);

        $form->display('id', 'Код');
        $form->select('initiator', 'Инициатор')->options(self::INITIATORS)->required();
        $form->select('rate_status', 'Статус ставки')->options(self::RATE_STATUSES)->required();
        $form->text('name_uk', 'Название 🇺🇦')->required();
        $form->text('name_ru', 'Название 🇷🇺')->required();
        $form->text('name_en', 'Название 🇬🇧')->required();
        $form->currency('days', 'Дней')->default(1)->symbol('∑')->digits(0)->rules('required|numeric');
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
        return $this->showFields(DisputeProblem::findOrFail($id));
    }
}
