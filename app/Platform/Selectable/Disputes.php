<?php

namespace App\Platform\Selectable;

use App\Models\Dispute;
use Encore\Admin\Grid\Selectable;
use Encore\Admin\Grid\Selectable\Radio;

class Disputes extends Selectable
{
    public $model = Dispute::class;

    /**
     * Блок для формы.
     *
     * @return void
     */
    public function make()
    {
        $this->model()->where('status', Dispute::STATUS_IN_WORK);

        $this->createColumns();
    }

    /**
     * Данные для модалки.
     *
     * @return string
     */
    public function render()
    {
        $this->model()->whereDoesntHave('track');

        $this->createColumns();

        $this->appendRemoveBtn(true);
        $this->disableFeatures()->disableFilter()->disablePagination();
        $this->prependColumn('__modal_selector__', ' ')->displayUsing(Radio::class, [$this->key]);

        return $this->grid->render();
    }

    /**
     * Список столбцов.
     *
     * @return void
     */
    protected function createColumns()
    {
        $this->column('id', 'Код');
        $this->column('problem.name', 'Проблема');
        $this->column('user_id', 'Код клиента');
        $this->column('user.full_name', 'Клиент');
    }
}
