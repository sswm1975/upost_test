<?php

namespace App\Platform\Controllers\Settings;

use App\Models\Tax;
use App\Modules\Calculations;
use App\Platform\Controllers\AdminController;
use App\Platform\Selectable\Countries;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Jxlwqq\CodeMirror\CodeMirror;

class TaxController extends AdminController
{
    protected string $title = 'Налоги';
    protected string $icon = 'fa-code';
    protected bool $isCreateButtonRight = true;
    protected bool $enableDblClick = true;

    public function menu(): array
    {
        $counts = Tax::selectRaw('active, count(1) as total')
            ->groupBy('active')
            ->pluck('total', 'active')
            ->toArray();

        $statuses = VALUES_ACTING;
        foreach ($statuses as $status => $name) {
            $statuses[$status] = (object) [
                'name'  => $name,
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
        $grid = new Grid(new Tax);

        # FILTERS
        $grid->model()->where('active', request('status', VALUE_ACTIVE));

        $grid->actions(function ($actions) {
            $actions->disableDelete();
        });

        $grid->column('id', 'Код');
        $grid->column('name', 'Название');
        $grid->column('alias', 'Алиас');
        $grid->column('export_countries','Экспорт')->belongsToMany(Countries::class, 'tax_export');
        $grid->column('import_countries','Импорт')->belongsToMany(Countries::class, 'tax_import');
        $grid->column('active', 'Действует')->switch(SWITCH_YES_NO)->sortable();
        $grid->column('created_at', 'Создано');
        $grid->column('updated_at', 'Изменено');

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
//        Admin::disablePjax();

        # подгружаем стили подстветки для PHP-кода
        Admin::css(CodeMirror::ASSETS_PATH.'theme/3024-night.css');

        # подгружаем скрипт по тестированию PHP-кода
        Admin::script(self::scriptTesting());

        $form = new Form(new Tax);

        $form->tab('Основное', function ($form) {
            $form->text('name', 'Название')->placeholder('Название налога')->required();
            $form->text('alias', 'Алиас')->placeholder('Алиас')->required();
            $form->php('code', 'Скрипт')->height(310)->default("<?php\n\nreturn 0;")->required();
            $form->divider('<b>Тестирование</b>');
            $form->currency('order_summa_usd', 'ORDER_SUMMA_USD')->help('Сумма в долларах, макрос {<b style="color: #01a252">ORDER_SUMMA_USD</b>}');
            $form->html('<a class="btn btn-sm btn-danger js-run_script"><i class="fa fa-code"></i>&nbsp;&nbsp;RUN SCRIPT</a>');
        })->tab('Экспорт', function ($form) {
            $form->belongsToMany('export_countries', Countries::class, 'Страны');
        })->tab('Импорт', function ($form) {
            $form->belongsToMany('import_countries', Countries::class, 'Страны');
        })->tab('Инфо', function ($form) {
            $form->display('id', 'Код');
            $form->textarea('description', 'Описание')->rows(20)->required();
            $form->switch('active', 'Действует')->default(VALUE_ACTIVE)->states(SWITCH_YES_NO);
        })->footer(function ($footer) {
            $footer->disableReset();
        });

        $form->submitted(function (Form $form) {
            $form->ignore('order_summa_usd');
        });

        return $form;
    }

    protected function detail($id): Show
    {
        Admin::style('.col-md-12:nth-child(2) {width:30%}');

        $script = Tax::with(['export_countries', 'import_countries'])->findOrFail($id);

        $show = new Show($script);

        $show->field('id', 'Код');
        $show->field('name', 'Название');
        $show->field('alias', 'Алиас');
        $show->field('active', 'Действует')->using(['Нет', 'Да']);
        $show->field('active', 'Действует')->using(['Нет', 'Да']);
        $show->field('code', 'Скрипт')->unescape()->as(function ($code) {
            return highlightText($code);
        });
        $show->field('description', 'Описание')->unescape()->as(function ($description) {
            return "<pre>{$description}</pre>";
        });
        $show->export_countries('Экспорт', function ($country) {
            $country->disableFilter();
            $country->disableExport();
            $country->disablePagination();
            $country->disableRowSelector();
            $country->disableColumnSelector();
            $country->disableActions();
            $country->disableCreateButton();

            $country->column('id', 'Код');
            $country->column('name_ru', 'Наименование');
        });
        $show->import_countries('Импорт', function ($country) {
            $country->disableFilter();
            $country->disableExport();
            $country->disablePagination();
            $country->disableRowSelector();
            $country->disableColumnSelector();
            $country->disableActions();
            $country->disableCreateButton();

            $country->column('id', 'Код');
            $country->column('name_ru', 'Наименование');
        });

        return $show;
    }

    /**
     * Выполнить PHP-скрипт.
     *
     * @param Request $request
     * @return int
     */
    public function runScript(Request $request): int
    {
        $code = $request->get('code', '');
        $amount = $request->get('order_summa_usd', 0);

        return Calculations::runScript($code, $amount);
    }

    /**
     * JS-скрипт обработки кнопки "Запустить скрипт".
     *
     * @return string
     */
    private static function scriptTesting(): string
    {
        $url = route('platform.settings.taxes.run_script');

        return <<<EOD

$('.js-run_script').off('click').on('click', function() {
    $.post('$url', {
        code: document.querySelector('.CodeMirror').CodeMirror.getValue(),
        order_summa_usd: $('#order_summa_usd').val().replace(',', ''),
        _token: LA.token
    })
    .done(function (response) {
        Swal.fire('Результат: ' + response, '', 'success')
    })
    .fail(function (jqXHR, textStatus) {
        Swal.fire(jqXHR.responseJSON.message, 'Ошибка ' + jqXHR.status, 'error');
    });
});

EOD;
    }
}
