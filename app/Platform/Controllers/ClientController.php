<?php

namespace App\Platform\Controllers;

use App\Models\City;
use App\Models\User;
use App\Platform\Exporters\ClientExcelExporter;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ClientController extends AdminController
{
    protected string $title = 'Клиенты';
    protected string $icon = 'fa-users';

    /**
     * Меню в разрезе статусов.
     *
     * @return array
     */
    public function menu(): array
    {
        $counts = User::selectRaw('status, count(1) as total')
            ->where('id', '>', 0)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statuses = [];
        foreach (User::STATUSES as $status) {
            $statuses[$status] = (object) [
                'name'  => __("message.user.statuses.$status"),
                'count' => $counts[$status] ?? 0,
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
        $grid = new Grid(new User);

        # SETTINGS GRID
        $grid->disablePagination(false);
        $grid->disableFilter(false);
        $grid->disableExport(false);
        $grid->disableColumnSelector(false);
        $grid->disableCreateButton();
        $grid->paginate(20);

        # FILTERS
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->scope('role_user', '👥 Пользователи')->whereRole(User::ROLE_USER)->asDefault();
            $filter->scope('role_admin', '🕵️‍ Админы')->whereRole(User::ROLE_ADMIN);
            $filter->scope('role_moderator', '😈 Модераторы')->whereRole(User::ROLE_MODERATOR);

            $filter->column(1 / 2, function ($filter) {
                $filter->equal('id', 'Код');
                $filter->in('city_id', 'Город')->multipleSelect(
                    # при экспорте формировать список для фильтра нет необходимости
                    request()->missing('_export_')
                        ? City::pluck('name_ru', 'id')
                        : []
                );
            });

            $filter->column(1 / 2, function ($filter) {
                $filter->group('scores_count', 'Кол-во баллов', function ($group) {
                    $group->gt('больше чем');
                    $group->lt('меньше чем');
                    $group->nlt('не меньше чем');
                    $group->ngt('не больше чем');
                    $group->equal('равно');
                    $group->notEqual('не равно');
                });
                $filter->group('reviews_count', 'Кол-во отзывов', function ($group) {
                    $group->gt('больше чем');
                    $group->lt('меньше чем');
                    $group->nlt('не меньше чем');
                    $group->ngt('не больше чем');
                    $group->equal('равно');
                    $group->notEqual('не равно');
                });
            });
        });

        $grid->quickSearch(function ($model, $query) {
            $model->where(function($model) use ($query) {
                $model->where('name', 'like', "%{$query}%")
                    ->orWhere('surname', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            });
        })->placeholder('Поиск по имени, телефону, емейлу');

        # MODEL FILTERS & SORT
        $grid->model()->where('status', request('status', User::STATUS_ACTIVE));
        if (! request()->has('_sort')) {
            $grid->model()->latest('id');
        }

        # ROW ACTIONS
        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        # COLUMNS
        $grid->column('id', 'Код')->setAttributes(['align' => 'center'])->sortable();
        $grid->column('surname', 'Фамилия')->sortable();
        $grid->column('name', 'Имя')->sortable();
        $grid->column('phone', 'Телефон')->sortable();
        $grid->column('email', 'Емейл')->sortable();
        $grid->column('gender', 'Пол')
            ->filter(
                array_combine(User::GENDERS, array_map(function ($gender) {
                    return __("message.user.genders.$gender");
                }, User::GENDERS))
            )
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
            ->replace(ADMIN_LANGUAGES)
            ->filter(ADMIN_LANGUAGES)
            ->setAttributes(['align'=>'center'])
            ->sortable();
        $grid->column('card_number', '№ карточки')->filter('like')->sortable();
        $grid->column('card_name', 'Имя на карте')->sortable();
        $grid->column('resume_modal', 'Резюме')
            ->modal('Резюме', function () {
                $resume = $this->resume ?: '<h3>Резюме не заполнено</h3>';
                return "
                    <div>
                        <div style='width: 20%; float: left; padding-right: 10px;'>
                            <img src='$this->photo' class='img img-thumbnail'>
                        </div>
                        <div style='width: 80%; float: left; text-align: left;'>
                            {$resume}
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
        $grid->column('scores_count', 'К-во баллов')->sortable();
        $grid->column('reviews_count', 'К-во отзывов')->sortable();
        $grid->column('failed_delivery_count', 'К-во неуд.доставок')->sortable();
        $grid->column('failed_receive_count', 'К-во неуд.получений')->sortable();
        $grid->column('rating', 'Рейтинг');
        $grid->column('google_id', 'Код Гугл');
        $grid->column('facebook_id', 'Код Фейсбук');
        $grid->column('created_at', 'Добавлено')->sortable();
        $grid->column('updated_at', 'Изменено')->sortable();

        # EXPORT TO EXCEL
        $grid->exporter(new ClientExcelExporter);

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
        dd(User::findOrFail($id));
        return $this->showFields(User::findOrFail($id));
    }
}
