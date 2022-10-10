<?php

namespace App\Platform\Extensions\Grid\Actions;

use Encore\Admin\Actions\RowAction;
use Encore\Admin\Actions\Response;
use Illuminate\Database\Eloquent\Model;

class Replicate extends RowAction
{
    /**
     * Name action name.
     *
     * @return array|null|string
     */
    public function name()
    {
        return 'Клонировать';
    }

    /**
     * Proccess clone operation.
     *
     * @param Model $model
     *
     * @return Response
     */
    public function handle(Model $model)
    {
        session()->flash('_old_input', $model->toArray());

        $referer = parse_url(request()->header('referer'), PHP_URL_PATH);
        $create_url = $referer . '/create';

        return $this
            ->response()
            ->redirect($create_url)
            ->toastr()
            ->success('Успешно склонировано');
    }

    /**
     * Show dialog box.
     *
     * @return void
     */
    public function dialog()
    {
        $this->question(
            'Вы уверены, что хотите клонировать эту запись?',
            '',
            [
                'confirmButtonText'  => 'Да',
                'confirmButtonColor' => '#d33',
            ]
        );
    }

}