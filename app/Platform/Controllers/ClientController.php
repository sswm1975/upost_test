<?php

namespace App\Platform\Controllers;

use App\Models\User;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Storage;
use App\Platform\Extensions\Exporters\ExcelExpoter;

class ClientController extends AdminController
{
    protected string $title = 'Клиенты';
    protected string $icon = 'fa-users';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new User);

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
            ->setAttributes(['align'=>'right'])
            ->sortable();

        $grid->column('currency', 'Валюта')
            ->filter(array_combine(config('app.currencies'), config('app.currencies')))
            ->setAttributes(['align'=>'center'])
            ->sortable();

        $grid->column('lang', 'Язык')
            ->display(function ($lang) {
                return ADMIN_LANGUAGES[$lang];
            })
            ->filter(ADMIN_LANGUAGES)
            ->setAttributes(['align'=>'center'])
            ->sortable();

        $grid->column('card_number', '№ карточки')
            ->filter('like')
            ->sortable();

        $grid->column('card_name', 'Имя на карте')->sortable();

        $grid->column('resume_modal', 'Резюме')
            ->modal('Резюме', function () {
                return "
                    <div>
                        <div style='width: 20%; float: left; padding-right: 10px;'>
                            <img src='$this->photo' class='img img-thumbnail'>
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
        $grid->exporter(new ExcelExpoter);

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
        return $this->addShowFields(new Show(User::findOrFail($id)));
    }
}
