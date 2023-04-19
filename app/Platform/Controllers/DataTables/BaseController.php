<?php

namespace App\Platform\Controllers\DataTables;

use App\Http\Controllers\Controller;
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
     * View template for table.
     *
     * @var string
     */
    protected string $view = '';

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
        $this->scriptDataTable();

        $content->title($this->title())
            ->description('&nbsp;')
            ->breadcrumb(...$this->breadcrumb());

        return $content->body($this->table());
    }

    /**
     * @return string
     */
    protected function table()
    {
        return view($this->view);
    }
}
