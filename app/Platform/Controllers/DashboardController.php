<?php

namespace App\Platform\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;

class DashboardController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Главная')
            ->description('Описание...')
            ->row('Проверка');
    }
}
