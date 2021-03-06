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

use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid\Column;
use App\Platform\Extensions\Grid\Displayers\AjaxModal;
use App\Platform\Extensions\Grid\Displayers\Orderable;
use App\Platform\Extensions\Nav;
use Encore\Admin\Show;

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

Admin::favicon(config('app.url').'/favicon.png');

if (in_array(request()->getRequestUri(), ['/platform/auth/login', '/platform/auth/logout'])) return;

# Разрешаем перетаскивать модалки
Admin::js('vendor/laravel-admin/AdminLTE/plugins/jQuery/draggable.min.js');
Admin::script("$('.modal-dialog').draggable({handle: '.modal-header'});");

Admin::js('https://unpkg.com/chart.js@3.8.0/dist/chart.min.js');
Admin::js('https://unpkg.com/@chartisan/chartjs@2.1.0/dist/chartisan_chartjs.umd.js');

# Подгружаем fix-стили
Admin::style(<<<CSS
    /* Меню в сайдбаре: увеличиваем ширину пунктов меню */
    .sidebar-menu .treeview span {width:230px !important;}
    .sidebar-menu .treeview .treeview-menu {width:230px !important;}

    /* Класс для запрета переноса строк */
    .nowrap {white-space: nowrap;}
CSS);


Admin::navbar(function (\Encore\Admin\Widgets\Navbar $navbar) {
//    if (Admin::user()->inRoles(['administrator', 'dispute_manager'])) {
//        $navbar->right(new Nav\DisputesCounter);
//    }
    $navbar->right(new Nav\FullScreen());
});


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

Show::init(function (Show $show) {
    $show->panel()->tools(function ($tools) {
        $tools->disableEdit();
        $tools->disableDelete();
    });
});

Column::extend('ajaxModal', AjaxModal::class);
Column::extend('orderable', Orderable::class);
Column::extend('showYesNo', function ($value) {
    return $value
        ? '<span class="label label-success">Да</span>'
        : '<span class="label label-danger">Нет</span>';
});
Column::extend('showOtherField', function ($value, $field) {
    return !empty($value) ? $this->$field : '';
});

Column::extend('price', function ($value, $decimals = 2, $decimal_separator = ".", $thousands_separator = ",") {
    return number_format($value, $decimals, $decimal_separator, $thousands_separator);
});

