<?php

namespace App\Platform\Selectable;

use App\Models\Country;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Grid\Selectable;

class Countries extends Selectable
{
    public $model = Country::class;

    public function make()
    {
        $this->column('id', 'Код');
        $this->column('name_ru', 'Название');

        $this->filter(function (Filter $filter) {
            $filter->like('name_ru');
        });
    }

    public static function display()
    {
        return function ($value) {
            if (is_array($value)) {
                return implode(';', array_column($value,'name_ru'));
            }

            return '';
        };
    }
}
