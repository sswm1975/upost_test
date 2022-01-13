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

use Encore\Admin\Grid\Column;
use App\Platform\Extensions\Grid\Displayers\AjaxModal;

Encore\Admin\Form::forget(['map', 'editor']);

Admin::favicon(config('app.url').'favicon.png');

# Разрешаем перетаскивать модалки
Admin::js('vendor/laravel-admin/AdminLTE/plugins/jQuery/draggable.min.js');
Admin::script("$('.modal-dialog').draggable({handle: '.modal-header'});");

Column::extend('ajaxModal', AjaxModal::class);
