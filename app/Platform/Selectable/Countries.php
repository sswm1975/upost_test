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
        $this->column('name_en', 'Название EN');
        $this->column('name_uk', 'Название UK');
        $this->column('name_ru', 'Название RU');

        $this->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            $filter->equal('id', 'Код')->placeholder('Код страны');
            $filter->where(function ($query) {
                $query->where('name_en', 'like', "%{$this->input}%");
                $query->orwhere('name_uk', 'like', "%{$this->input}%");
                $query->orwhere('name_ru', 'like', "%{$this->input}%");
            }, 'Название')->placeholder('Название страны');

        });
    }

    public static function display()
    {
        return function ($value) {
            if (is_array($value)) {
                return implode(';', array_column($value,'id'));
            }

            return '';
        };
    }
}
