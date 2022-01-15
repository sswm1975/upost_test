<?php

/**
 * Laravel-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * Encore\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * Encore\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

use Encore\Admin\Form;
use Encore\Admin\Grid\Column;
use App\Platform\Extensions\Grid\Displayers\AjaxModal;
use App\Platform\Extensions\Grid\Displayers\Orderable;
use App\Platform\Extensions\Nav;

/**
 * Статусы для переключателя SWITCH
 *
 * @var array
 */
const SWITCH_YES_NO = [
    'on'  => ['value' => '1', 'text' => 'Да', 'color' => 'success'],
    'off' => ['value' => '0', 'text' => 'Нет', 'color' => 'danger'],
];

const ADMIN_LANGUAGES = [
    'uk' => '🇺🇦',
    'ru' => '🇷🇺',
    'en' => '🇬🇧',
];

Admin::favicon(config('app.url').'favicon.png');

# Разрешаем перетаскивать модалки
Admin::js('vendor/laravel-admin/AdminLTE/plugins/jQuery/draggable.min.js');
Admin::script("$('.modal-dialog').draggable({handle: '.modal-header'});");

# Двойной клик на гриде: Переход на просмотр записи
Admin::script('$("table tbody tr").off("dblclick").on("dblclick", function(){$.pjax({url:"/' . implode('/', request()->segments()) . '/" + $(this).data("key"), container:"#pjax-container"})});');

Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {
    $navbar->right(new Nav\FullScreen());
});

Column::extend('ajaxModal', AjaxModal::class);
Column::extend('orderable', Orderable::class);

Form::forget(['map', 'editor']);

Form::init(function (Form $form) {
    $form->disableEditingCheck();
    $form->disableCreatingCheck();
    $form->disableViewCheck();

    $form->tools(function (Form\Tools $tools) {
        $tools->disableDelete();
        $tools->disableView();
    });
});
