<?php

namespace App\Platform\Controllers;

use App\Models\Payment;
use App\Platform\Actions\Payment\AppointPayment;
use App\Platform\Actions\Payment\RejectPayment;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;

class PaymentController extends AdminController
{
    protected string $title = 'Заявки на выплату';
    protected string $icon = 'fa-money';

    /**
     * Формируем список меню в разрезе статусов споров.
     *
     * @return string
     */
    public function menu(): string
    {
        $counts = Payment::selectRaw('status, count(1) as total')
            ->when(! Admin::user()->isAdministrator(), function ($query) {
                return $query->where('admin_user_id', Admin::user()->id);
            })
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (Payment::STATUSES as $status) {
            if (Admin::user()->isRole('dispute_manager') && $status == Payment::STATUS_ACTIVE) continue;

            $statuses[$status] = [
                'name' => __("message.payment.statuses.$status"),
                'count' => $counts[$status] ?? 0,
            ];
        }

        return view('platform.payments.menu')->with('statuses', $statuses);
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $status = request('status', Payment::STATUS_ACTIVE);

        $grid = new Grid(new Payment);

        $grid->disablePagination(false);
        $grid->disableColumnSelector(false);
        $grid->disableRowSelector(false);
        $grid->disableCreateButton();
        $grid->paginate(20);

        # Батчевые операции
        $grid->batchActions(function ($batch) use ($status) {
            # Назначить спор менеджеру
            if (Admin::user()->isAdministrator() && $status == Payment::STATUS_ACTIVE) {
                $batch->add(new AppointPayment);
            }

            # Отклонить платеж
            if (Admin::user()->isAdministrator() && in_array($status, [Payment::STATUS_ACTIVE, Payment::STATUS_APPOINTED])) {
                $batch->add(new RejectPayment);
            }
        });

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            if (! Admin::user()->isAdministrator()) {
                $actions->disableView();
                $actions->disableEdit();
            }
        });

        # FILTERS
        if (Admin::user()->isRole('dispute_manager')) {
            $grid->model()->where('admin_user_id', Admin::user()->id);
        }
        $grid->model()->where('status', $status);

        # COLUMNS
        $grid->column('id', 'Код')->sortable();
        $grid->column('user_id', 'Код К.')->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('user.card_number', 'Карта клиента');
        $grid->column('amount', 'Сумма');
        $grid->column('description', 'Описание заявки');
        $grid->column('status', 'Статус')->showOtherField('status_name')->sortable();

        if ($status != Payment::STATUS_ACTIVE) {
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
