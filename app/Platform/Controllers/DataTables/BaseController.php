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
     * Count of columns in table.
     *
     * @var int
     */
    protected int $count_columns = 0;

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
        static::styleCommon();

        $this->script();

        $content->title($this->title())
            ->description('&nbsp;')
            ->breadcrumb(...$this->breadcrumb());

        # если есть в классе наследнике метод menu, то подгружаем доп.стили и меню
        if (method_exists($this, 'menu')) {
            static::styleMenu();
            $content->row(
                view('platform.datatables.menu', $this->menu())
            );
        }

        $view = view('platform.datatables.' . $this->entity . '.table', ['count_columns' => $this->count_columns]);

        return $content->body($view);
    }

    /**
     * Load DataTables script.
     */
    protected function script()
    {
        # инициализируем ссылку для AJAX-запроса
        $ajax_url = route('platform.ajax.' . $this->entity);
        $script_content = "var ajax_url = '$ajax_url';";

        # добавляем объявление столбцов
        $script_content .= getScript('platform.datatables.' . $this->entity . '.init');

        # добавляем инициализацию DataTables
        $script_content .= getScript('platform.datatables.common');

        Admin::script($script_content);
    }

    /**
     * Get common style.
     */
    protected static function styleCommon()
    {
        Admin::style(getCss('platform.datatables.common'));
    }

    /**
     * Get menu style.
     */
    protected static function styleMenu()
    {
        Admin::style(<<<EOT
            .box.grid-box {border-top: 0;}
            .nav-statuses li:first-child {margin-left: 10px;}
            .nav-statuses li a {padding:4px 7px;}
            .nav-statuses li a.active {border-color: lightgray; background: white; border-bottom: 1px solid white;}
            .nav-statuses .label {padding: 0.1em 0.3em; border-radius: 50%;}

            div.dataTables_wrapper {background: white; padding-top: 10px; padding-bottom: 4px;}
            div.dataTables_wrapper div.dt-buttons {padding-left: 10px;}
            div.dataTables_wrapper div.dataTables_filter {padding-right: 10px;}
EOT);
    }
}
