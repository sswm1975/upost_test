<?php

namespace App\Platform\Controllers;

use App\Models\Transaction;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TransactionController extends AdminController
{
    protected string $title = 'Транзакции';
    protected string $icon = 'fa-retweet';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Transaction);

        $grid->disablePagination(false)
            ->paginate(20)
            ->disableCreateButton()
            ->disableActions();

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user_id', 'Код К.')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('amount', 'Сумма (всего)')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('order_amount', 'Сумма заказа')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('delivery_amount', 'Доставка')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('liqpay_fee', 'Ликпей')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('service_fee', 'Сервис')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('export_tax', 'Налог')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('description', 'Описание');
        $grid->column('status', 'Статус')->sortable();
        $grid->column('created_at', 'Создано')->sortable();
        $grid->column('updated_at', 'Изменено')->sortable();
        $grid->column('payed_at', 'Дата оплаты')->sortable();

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id): Show
    {
        return $this->showFields(Transaction::findOrFail($id));
    }
}
