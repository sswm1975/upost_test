<?php

namespace App\Platform\Controllers;

use App\Models\City;
use App\Models\User;
use Encore\Admin\Grid;
use Encore\Admin\Show;
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
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->scope('role_user', '👥 Пользователи')->whereRole(User::ROLE_USER)->asDefault();
            $filter->scope('role_admin', '🕵️‍ Админы')->whereRole(User::ROLE_ADMIN);
            $filter->scope('role_moderator', '😈 Модераторы')->whereRole(User::ROLE_MODERATOR);

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('id', 'Код');
                $filter->in('city_id', 'Город')->multipleSelect(City::pluck('name_ru', 'id'));
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->group('creator_rating', 'Рейтинг заказчика', function ($group) {
                    $group->gt('больше чем');
                    $group->lt('меньше чем');
                    $group->nlt('не меньше чем');
                    $group->ngt('не больше чем');
                    $group->equal('равно');
                    $group->notEqual('не равно');
                });
                $filter->group('freelancer_rating', 'Рейтинг исполнителя', function ($group) {
                    $group->gt('больше чем');
                    $group->lt('меньше чем');
                    $group->nlt('не меньше чем');
                    $group->ngt('не больше чем');
                    $group->equal('равно');
                    $group->notEqual('не равно');
                });
            });
        });

        $grid->selector(function (Grid\Tools\Selector $selector) {
            $selector->select('status', 'СТАТУС: ', [
                User::STATUS_ACTIVE     => __('message.user.statuses.' . User::STATUS_ACTIVE),
                User::STATUS_NOT_ACTIVE => __('message.user.statuses.' . User::STATUS_NOT_ACTIVE),
                User::STATUS_BANNED     => __('message.user.statuses.' . User::STATUS_BANNED),
                User::STATUS_REMOVED    => __('message.user.statuses.' . User::STATUS_REMOVED),
            ]);
            $selector->select('gender', 'ПОЛ:', [
                User::GENDER_MALE    => __('message.user.genders.' . User::GENDER_MALE),
                User::GENDER_FEMALE  => __('message.user.genders.' . User::GENDER_FEMALE),
                User::GENDER_UNKNOWN => __('message.user.genders.' . User::GENDER_UNKNOWN),
            ]);
        });

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
            ->showOtherField('gender_name')
            ->label(['unknown' => 'danger', 'male' => 'primary', 'female' => 'warning'])
            ->sortable();
        $grid->column('birthday', 'Дата рождения')->sortable();
        $grid->column('city.name', 'Город');
        $grid->column('wallet', 'Баланс')->filter('range')->setAttributes(['align'=>'right'])->sortable();
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
        $grid->column('card_number', '№ карточки')->filter('like')->sortable();
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
            })
            ->setAttributes(['align'=>'center']);
        $grid->column('status', 'Статус')->showOtherField('status_name')->sortable();
        $grid->column('validation', 'Валидация')->showOtherField('validation_name')->sortable();
        $grid->column('register_date', 'Зарегистрирован')->sortable();
        $grid->column('last_active', 'Последняя активность')->sortable();
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
        return $this->showFields(User::findOrFail($id));
    }
}
