<?php

namespace App\Platform\Extensions\Grid\Displayers;

use Encore\Admin\Grid\Displayers\AbstractDisplayer;
use Encore\Admin\Admin;
use Illuminate\Contracts\Support\Renderable;

class AjaxModal extends AbstractDisplayer
{
    protected function addRenderableModalScript()
    {
        $url = route('admin.handle-renderable');

        $script = <<<SCRIPT
(function () {
    $('#grid-ajax-modal').on('show.bs.modal', function (e) {
        var title = $(this).find('.modal-title');
        var body = $(this).find('.modal-body')
        var renderable = $(e.relatedTarget).data('renderable');
        var key = $(e.relatedTarget).data('key');

        title.html('Загрузка...');
        body.button('loading');

        $.get('{$url}'+'?renderable='+renderable+'&key='+key, function (data) {
            title.html(data.title || '&nbsp;');
            body.html(data.content || '');
        });
    })
})();
SCRIPT;

        Admin::script($script);
    }

    protected function getModalHtml()
    {
        return <<<EOT
<div class="modal" id="grid-ajax-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">&nbsp;</h4>
      </div>
      <div class="modal-body" data-loading-text='<div class="loading text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>'>
      </div>
    </div>
  </div>
</div>
EOT;
    }

    public function display($renderable = null)
    {
        if (func_num_args() != 1 || !is_subclass_of($renderable, Renderable::class)) {
            throw new \InvalidArgumentException("Invalid argument or argument is not Renderable class.");
        }

        if (empty($this->value)) {
            return '';
        }

        $this->addRenderableModalScript();

        $html = $this->getModalHtml();
        if (!in_array($html, Admin::$html)) {
            Admin::html($html);
        }

        $renderable = str_replace('\\', '_', $renderable);
        $value = is_array($this->value) ? count($this->value) : $this->value;

        return <<<EOT
<a href="javascript:void(0)" data-toggle="modal" data-target="#grid-ajax-modal" data-renderable="{$renderable}" data-key="{$this->getKey()}">
    <i class="fa fa-clone"></i>&nbsp;&nbsp;{$value}
</a>
EOT;
    }
}
