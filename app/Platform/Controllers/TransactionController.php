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

        # MODEL FILTERS & SORT
        if (! request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user_id', 'Код К.')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('user.full_name', 'Клиент');
        $grid->column('amount', 'Сумма (всего)')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('order_amount', 'Сумма заказа')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('delivery_amount', 'Доставка')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('payment_service_fee', 'Комиссия ПС')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('company_fee', 'Комиссия компании')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('export_tax', 'Экспортный налог')->setAttributes(['align' => 'right'])->sortable();
        $grid->column('description', 'Описание');

        $grid->column('purchase_params_modal', 'Purchase Параметры')
            ->modal('Параметры для PayPal purchase', function () {
                return pretty_print($this->purchase_params);
            })
            ->setAttributes(['align'=>'center']);

        $grid->column('purchase_response_modal', 'Purchase Ответ')
            ->modal('Ответ от PayPal purchase', function () {
                return pretty_print($this->purchase_response);
            })
            ->setAttributes(['align'=>'center']);

        $grid->column('purchase_redirect_url', 'Ссылка для оплаты в PayPal')->url();
        $grid->column('purchase_error')->help('Ошибка при purchase (статус failed)');
        $grid->column('purchase_exception')->help('Исключение при purchase (статус exception)');

        $grid->column('complete_response_modal', 'Ответ complete PayPal')
            ->modal('Ответ от сервиса PayPal (complete)', function () {
                return pretty_print($this->complete_response);
            })
            ->setAttributes(['align'=>'center']);

        $grid->column('complete_error_modal', 'Ошибка complete PayPal')
            ->modal('Ответ от сервиса PayPal (complete)', function () {
                return pretty_print($this->complete_error);
            })
            ->setAttributes(['align'=>'center']);

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
