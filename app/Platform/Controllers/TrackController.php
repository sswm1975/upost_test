<?php

namespace App\Platform\Controllers;

use App\Models\Track;
use App\Platform\Actions\Track\GoodsFailed;
use App\Platform\Actions\Track\GoodsReceived;
use App\Platform\Actions\Track\GoodsVerified;
use App\Platform\Actions\Track\SentTTN;
use App\Platform\Selectable\Disputes;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TrackController extends AdminController
{
    protected string $title = 'Треки доставки';
    protected string $icon = 'fa-truck';
    protected bool $isCreateButtonRight = true;
    protected bool $enableDropdownAction = true;

    /**
     * Формируем список меню в разрезе статусов споров.
     *
     * @return array
     */
    public function menu(): array
    {
        $counts = Track::selectRaw('status, count(1) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (Track::STATUSES as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
                'color' => Track::STATUS_COLORS[$status] ?? '',
            ];
        }

        return compact('statuses');
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    public function grid(): Grid
    {
        $grid = new Grid(new Track);

        $grid->disablePagination(false)->paginate(20);

        $grid->quickSearch('ttn')->placeholder('Поиск на ТТН');

        # ROW ACTIONS
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableView();

            # Только для статуса новый активны операции Редактировать и Удалить
            if ($actions->row->status != Track::STATUS_NEW) {
                $actions->disableEdit();
                $actions->disableDelete();
            }

            # Отправить ТТН
            if ($actions->row->status == Track::STATUS_NEW) {
                $actions->add(new SentTTN);
            }

            # Товар получен
            if ($actions->row->status == Track::STATUS_SENT) {
                $actions->add(new GoodsReceived);
            }

            # Товар проверен или испорчен
            if ($actions->row->status == Track::STATUS_RECEIVED) {
                $actions->add(new GoodsVerified);
                $actions->add(new GoodsFailed);
            }

            # Товар все-таки испорчен
            if ($actions->row->status == Track::STATUS_VERIFIED) {
                $actions->add(new GoodsFailed);
            }
        });

        # FILTERS & SORT
        $grid->model()->where('status', request('status', Track::STATUS_NEW));
        if (! request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('ttn', 'ТТН')->copyable()->sortable();
        $grid->column('dispute_id', 'Спор');
        $grid->column('status' , 'Статус')->replace(Track::STATUSES);
        $grid->column('created_at', 'Дата добавления')->sortable();
        $grid->column('updated_at', 'Дата изменения')->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form(): Form
    {
        $form = new Form(new Track);

        $form->display('id', 'Код');
        $form->text('ttn', 'ТТН')->required();
        $form->belongsTo('dispute_id', Disputes::class, 'Спор')->required();

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
        return $this->showFields(Track::findOrFail($id));
    }
}
