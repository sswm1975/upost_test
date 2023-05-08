<?php

namespace App\Platform\Extensions\Nav;

use App\Models\Dispute;
use Illuminate\Contracts\Support\Renderable;
use Encore\Admin\Facades\Admin;

class DisputesCounter implements Renderable
{
    public function render()
    {
        Admin::script($this->script());

        $url = route('platform.disputes.index', [
            'status' => Admin::user()->isAdministrator() ? Dispute::STATUS_ACTIVE : Dispute::STATUS_APPOINTED
        ]);

        return <<<HTML
<li>
    <a href="$url">
      <i class="fa fa-gavel"></i>
      <span class="label label-danger" id="js-disputes_counter"></span>
    </a>
</li>

HTML;
    }

    private function script(): string
    {
        $url = route('platform.old.disputes.counter', [
            'status' => Admin::user()->isAdministrator() ? Dispute::STATUS_ACTIVE : Dispute::STATUS_APPOINTED,
            'admin_user_id' => Admin::user()->isAdministrator() ? 0 : Admin::user()->id,
        ]);

        $message = Admin::user()->isAdministrator() ? 'Поступ новый спор!': 'Вам назначен спор!';

        return <<<SCRIPT

function update_disputes_counter() {
    let js_disputes_counter = $('.navbar-custom-menu').find('#js-disputes_counter');
    let disputes_counter = js_disputes_counter.text();

    $.get('$url', function(response) {
        if (response.value > disputes_counter) {
            $.admin.toastr.warning('$message', 'Внимание', {positionClass:"toast-top-center"});
        }
        js_disputes_counter.text(response.value ? response.value : '');
    });
};

update_disputes_counter();

SCRIPT;
    }
}
