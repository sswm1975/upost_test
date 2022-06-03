<?php

namespace App\Platform\Controllers;

use App\Models\Payment;
use App\Platform\Actions\Payment\AppointPayment;
use App\Platform\Actions\Payment\DonePayment;
use App\Platform\Actions\Payment\RejectPayment;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class PaymentController extends AdminController
{
    protected string $title = 'Заявки на выплату';
    protected string $icon = 'fa-money';
    protected bool $enableDropdownAction = true;

    /**
     * Формируем список меню в разрезе статусов платежа.
     *
     * @return array
     */
    public function menu(): array
    {
        $counts = Payment::selectRaw('status, count(1) as total')
            ->when(! Admin::user()->isAdministrator(), function ($query) {
                return $query->where('admin_user_id', Admin::user()->id);
            })
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (Payment::STATUSES as $status => $name) {
            if (Admin::user()->isRole('finance_manager') && $status == Payment::STATUS_NEW) continue;

            $statuses[$status] = (object) [
                'name'  => $name,
                'count' => $counts[$status] ?? 0,
                'color' => Payment::STATUS_COLORS[$status] ?? '',
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
        $status = request('status', Payment::STATUS_NEW);

        $grid = new Grid(new Payment);

        $grid->disableCreateButton()
            ->disablePagination(false)
            ->paginate(20);

        # ROW ACTIONS
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            $actions->disableEdit();

            # Назначить заявку финансовому менеджеру
            if (Admin::user()->isAdministrator() && $actions->row->status == Payment::STATUS_NEW) {
                $actions->add(new AppointPayment);
            }

            # Выполнена заявка
            if ($actions->row->status == Payment::STATUS_APPOINTED) {
                $actions->add(new DonePayment);
            }

            # Отклонить заявку
            if (in_array($actions->row->status, [Payment::STATUS_NEW, Payment::STATUS_APPOINTED])) {
                $actions->add(new RejectPayment);
            }
        });

        # FILTERS & SORT
        $grid->model()->where('status', $status);
        if (Admin::user()->isRole('finance_manager')) {
            $grid->model()->where('admin_user_id', Admin::user()->id);
        }
        if (! request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user_id', 'Код К.')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('user.card_number', 'Карта клиента');
        $grid->column('amount', 'Сумма')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('description', 'Описание заявки');
        $grid->column('status', 'Статус')->replace(Payment::STATUSES)->sortable();

        if ($status != Payment::STATUS_NEW) {
            $grid->column('admin_user_id', 'Код М.')->sortable();
            $grid->column('admin_user.username', 'Менеджер');
        }
        $grid->column('created_at', 'Создано')->sortable();
        $grid->column('updated_at', 'Изменено')->sortable();

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        $form = new Form(new Payment);

        $form->display('id', 'Код');

        if (Admin::user()->isAdministrator()) {
            $statuses = [];
            foreach (Payment::STATUSES as $status) {
                $statuses[$status] = __("message.payment.statuses.$status");
            }
            $form->select('status', 'Статус')->options($statuses);
        }

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
        return $this->showFields(Payment::findOrFail($id));
    }
}
