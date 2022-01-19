<?php

namespace App\Platform\Extensions\Tools;

use Encore\Admin\Grid\Tools\BatchAction;

class SetRead extends BatchAction
{
    public function script()
    {
        return <<<EOT

$('{$this->getElementClass()}').on('click', function() {
    $.ajax({
        method: 'post',
        url: '{$this->resource}/set_read',
        data: {
            _token:LA.token,
            ids: $.admin.grid.selected().join(),
        },
        success: function (response) {
            if (response.status) {
                $.pjax.reload('#pjax-container');
                toastr.success('Установлен признак "Прочитано" по ' + response.affected_rows + ' записям');
            } else {
                toastr.warning('Все письма уже прочитаны!');
            }
        }
    });
});

EOT;
    }
}
