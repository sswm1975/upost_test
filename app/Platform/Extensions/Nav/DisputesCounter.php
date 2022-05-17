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
        $url = route('api.disputes.counter', [
            'status' => Admin::user()->isAdministrator() ? Dispute::STATUS_ACTIVE : Dispute::STATUS_APPOINTED,
            'admin_user_id' => Admin::user()->isAdministrator() ? 0 : Admin::user()->id,
        ]);

        return <<<SCRIPT

let disputes_counter = $('.navbar-custom-menu').find('#js-disputes_counter');

function update_disputes_counter() {
    $.get('$url', function(response) {
        disputes_counter.text(response.value ? response.value : '');
    });
}

setInterval(update_disputes_counter, 10000);

SCRIPT;
    }
}
