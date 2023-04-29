<?php

namespace App\Platform\Controllers\DataTables;

use App\Http\Controllers\Controller;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Route;

class BaseController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Font Awesome icon.
     *
     * @var string
     */
    protected string $icon = '';

    /**
     * Breadcrumb.
     *
     * @var array
     */
    protected array $breadcrumb = [];

    /**
     * Entity.
     *
     * @var string
     */
    protected string $entity = '';

    /**
     * Get content title.
     *
     * @return string
     */
    protected function title(): string
    {
        return ($this->icon ? "<i class='fa {$this->icon}'></i>&nbsp;" : '') . $this->title;
    }

    /**
     * Get breadcrumb.
     *
     * @param mixed $id
     * @return array
     */
    protected function breadcrumb($id = 0): array
    {
        $breadcrumb = array_merge($this->breadcrumb, [[
            'text' => $this->title, 'icon' => str_replace('fa-', '', $this->icon)
        ]]);

        if (Route::getCurrentRoute()->getActionMethod() == 'index') {
            return $breadcrumb;
        }

        if ($id) {
            $breadcrumb = array_merge($breadcrumb, [['text' => $id]]);
        }

        return array_merge($breadcrumb, [['text' => $this->description()]]);
    }

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content): Content
    {
        Admin::style(getCss('platform.datatables.common'));
        Admin::script($this->script());

        $content->title($this->title())
            ->description('&nbsp;')
            ->breadcrumb(...$this->breadcrumb());

        # если есть в классе наследнике метод menu, то подгружаем доп.стили и меню
        if (method_exists($this, 'menu')) {
            Admin::style(self::styleMenu());
            $content->row(
                view('platform.datatables.menu', $this->menu())
            );
        }

        return $content->body(view('platform.datatables.' . $this->entity . '.table'));
    }

    protected function script()
    {
        $script = 'platform.datatables.' . $this->entity . '.script';
        $ajax_url = route('platform.ajax.' . $this->entity);

        return getScript($script, compact('ajax_url'));
    }

    /**
     * Get menu style.
     *
     * @return string
     */
    protected static function styleMenu(): string
    {
        return <<<EOT
            .box.grid-box {border-top: 0;}
            .nav-statuses li:first-child {margin-left: 20px;}
            .nav-statuses li a {padding:4px 7px;}
            .nav-statuses li a.active {border-color: lightgray; background: white; border-bottom: 1px solid white;}
            .nav-statuses .label {padding: 0.1em 0.3em; border-radius: 50%;}

            div.dataTables_wrapper {background: white; padding-top: 10px; padding-bottom: 4px;}
            div.dataTables_wrapper div.dt-buttons {padding-left: 10px;}
            div.dataTables_wrapper div.dataTables_filter {padding-right: 10px;}
EOT;
    }
}
