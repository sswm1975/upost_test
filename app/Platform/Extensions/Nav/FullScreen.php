<?php

namespace App\Platform\Extensions\Nav;

use Illuminate\Contracts\Support\Renderable;
use Encore\Admin\Facades\Admin;

class FullScreen implements Renderable
{

    public function render()
    {
        $script = <<<SCRIPT

function launchFullscreen(element) {
  if(element.requestFullscreen) {
    element.requestFullscreen();
  } else if(element.mozRequestFullScreen) {
    element.mozRequestFullScreen();
  } else if(element.msRequestFullscreen){
    element.msRequestFullscreen();
  } else if(element.webkitRequestFullscreen) {
    element.webkitRequestFullScreen();
  }
}

function exitFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.msExitFullscreen) {
    document.msExitFullscreen();
  } else if (document.mozCancelFullScreen) {
    document.mozCancelFullScreen();
  } else if (document.webkitExitFullscreen) {
    document.webkitExitFullscreen();
  }
}

$('.nav-fullscreen').off('click').on('click', function() {
  if ($(this).hasClass('fullscreen')) {
    exitFullscreen();
  } else {
    launchFullscreen(document.body)
  }
  $(this).toggleClass('fullscreen');
});

SCRIPT;
        Admin::script($script);

        return <<<HTML
<li>
    <a href="javascript:void(0);" class="nav-fullscreen">
        <i class="fa fa-arrows-alt"></i>
    </a>
</li>

HTML;
    }
}