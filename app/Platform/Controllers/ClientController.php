<?php

namespace App\Platform\Controllers;

use App\Models\User;
use Encore\Admin\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;
use App\Platform\Extensions\Exporters\ExcelExpoter;

class ClientController extends AdminController
{
    protected string $title = 'Клиенты';
    protected string $icon = 'fa-users';
    protected bool $enableStyleIndex = true;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        Admin::style($this->style());
        Admin::script('$("table tr").off("dblclick").on("dblclick", function(){$.pjax({url:"/platform/clients/" + $(this).data("key"), container:"#pjax-container"})});');

        $grid = new Grid(new User());

        # SETTINGS GRID
        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name', 'like', "%{$query}%")
                    ->orWhere('surname', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            });
        })->placeholder('Поиск по имени, телефону, емейлу');

        $grid->disablePagination(false);
        $grid->disableFilter(false);
        $grid->disableExport(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->paginate(20);

        # COLUMNS
        $grid->column('id', 'Код')->sortable();

        $grid->column('surname', 'Фамилия')->sortable();

        $grid->column('name', 'Имя')->sortable();

        $grid->column('phone', 'Телефон')->sortable();

        $grid->column('email', 'Емейл')->sortable();

        $grid->column('gender', 'Пол')
            ->display(function(){
                return $this->gender_name;
            })
            ->sortable();

        $grid->column('birthday', 'Дата рождения')->sortable();

        $grid->column('city_id', 'Город')
            ->display(function ($value) {
                return !empty($value) ? $this->city->name : '';
            })
            ->sortable();

        $grid->column('wallet', 'Баланс')
            ->filter('range')
            ->sortable();

        $grid->column('currency', 'Валюта')
            ->filter(array_combine(config('app.currencies'), config('app.currencies')))
            ->sortable();

        $grid->column('lang', 'Язык')
            ->filter(array_combine(config('app.languages'), config('app.languages')))
            ->sortable();

        $grid->column('card_number', '№ карточки')
            ->filter('like')
            ->sortable();

        $grid->column('card_name', 'Имя на карте')->sortable();

        $grid->column('resume_modal', 'Резюме')
            ->modal('Резюме', function () {
                $src = url()->isValidUrl($this->photo) ? $this->photo : Storage::disk(config('admin.upload.disk'))->url($this->photo);
                return "
                    <div>
                        <div style='width: 20%; float: left; padding-right: 10px;'>
                            <img src='$src' class='img img-thumbnail'>
                        </div>
                        <div style='width: 80%; float: left;'>
                            {$this->resume}
                        </div>
                        <div style='clear:both; line-height: 0;'></div>
                    </div>
                ";
            });

        $grid->column('status', 'Статус')
            ->display(function(){
                return "<span style='white-space: nowrap'>{$this->status_name}</span>";
            })
            ->sortable();

        $grid->column('validation', 'Валидация')
            ->display(function(){
                return "<span style='white-space: nowrap'>{$this->validation_name}</span>";
            })->sortable();

        $grid->column('register_date', 'Зарегистрирован')
            ->display(function($value){
                return "<span style='white-space: nowrap'>$value</span>";
            })
            ->sortable();

        $grid->column('last_active', 'Последняя активность')
            ->display(function($value){
                return "<span style='white-space: nowrap'>$value</span>";
            })
            ->sortable();

        $grid->column('role', 'Роль')->sortable();

        $grid->column('creator_rating', 'Рейтинг заказчика')->sortable();

        $grid->column('freelancer_rating', 'Рейтинг исполнителя')->sortable();

        $grid->column('favorite_orders', 'Заказы');

        $grid->column('favorite_routes', 'Маршруты');

        $grid->column('google_id', 'Код Гугл');

        $grid->column('facebook_id', 'Код Фейсбук');

        # EXPORT TO EXCEL
        $grid->exporter(new ExcelExpoter());

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        return $this->addShowFields(new Show(User::findOrFail($id)));
    }

    /**
     * Styles for index interface.
     *
     * @return string
     */
    protected function style(): string
    {
        return <<<EOT
            .column-selector > ul.dropdown-menu {width: 255px;}
            table > thead > tr > th {white-space: nowrap; background:lightgrey;}
            table > tbody > tr td.column-manager_id {padding-right: 20px;}
            table > tbody > tr td.column-manager_sip {padding-right: 25px;}
            .modal-header{cursor: move;}
            table th, .dataTable th {font-size: 11px;}
            .modal-backdrop {opacity:0 !important;}
            ul.products {margin: 0; padding: 0 0 0 10px;}
EOT;
    }

}
