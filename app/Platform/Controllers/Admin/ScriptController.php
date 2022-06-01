<?php

namespace App\Platform\Controllers\Admin;

use App\Models\Script;
use App\Platform\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Form;
use App\Platform\Selectable\Countries;
use Jxlwqq\CodeMirror\CodeMirror;

class ScriptController extends AdminController
{
    protected string $title = 'Скрипты';
    protected string $icon = 'fa-code';
    protected bool $isCreateButtonRight = true;

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid(): Grid
    {
        $grid = new Grid(new Script);

        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->column('id', 'Код');
        $grid->column('name', 'Название');
        $grid->column('alias', 'Алиас');
        $grid->column('from_countries','Экспорт')->belongsToMany(Countries::class, 'script_from_country');
        $grid->column('to_countries','Импорт')->belongsToMany(Countries::class, 'script_to_country');
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
        # подгружаем стили подстветки для PHP-кода
        Admin::css(CodeMirror::ASSETS_PATH.'theme/3024-night.css');

        # подгружаем скрипт по тестированию PHP-кода
        Admin::script(self::scriptTesting());

        $form = new Form(new Script);

        $form->tab('Основное', function ($form) {
            $form->text('name', 'Название')->placeholder('Название скрипта')->required();
            $form->text('alias', 'Алиас')->placeholder('Алиас скрипта')->required();
            $form->php('code', 'Скрипт')->height(310)->default("<?php\n\nreturn 0;")->required();
            $form->divider('<b>Тестирование</b>');
            $form->currency('order_summa_usd', 'ORDER_SUMMA_USD')->help('Сумма в долларах, макрос {<b style="color: #01a252">ORDER_SUMMA_USD</b>}');
            $form->html('<a class="btn btn-sm btn-danger js-run_script"><i class="fa fa-code"></i>&nbsp;&nbsp;RUN SCRIPT</a>');
        })->tab('Экспорт', function ($form) {
            $form->belongsToMany('from_countries', Countries::class, 'Страны');
        })->tab('Импорт', function ($form) {
            $form->belongsToMany('to_countries', Countries::class, 'Страны');
        })->tab('Инфо', function ($form) {
            $form->display('id', 'Код');
            $form->textarea('description', 'Описание')->rows(20);
        })->footer(function ($footer) {
            $footer->disableReset();
        });

        $form->submitted(function (Form $form) {
            $form->ignore('order_summa_usd');
        });

        return $form;
    }

    /**
     * Выполнить PHP-скрипт.
     *
     * @return mixed
     */
    public function runScript()
    {
        $code = str_replace(
            ['<?php', '?>', '{ORDER_SUMMA_USD}'],
            ['', '', request('order_summa_usd', 0)],
            request('code', 'return 0;')
        );

        return eval($code);
    }

    /**
     * JS-скрипт обработки кнопки "Запустить скрипт".
     *
     * @return string
     */
    private static function scriptTesting(): string
    {
        $url = route('platform.scripts.run');

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
