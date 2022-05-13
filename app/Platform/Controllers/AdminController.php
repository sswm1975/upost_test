<?php

namespace App\Platform\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Layout\Content;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AdminController extends Controller
{
    use HasResourceActions;

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
     * Enable style loading for index page.
     *
     * @var bool
     */
    protected bool $enableStyleIndex = true;

    /**
     * Enable double click in grid for index page (Двойной клик на гриде: Переход на просмотр записи).
     *
     * @var bool
     */
    protected bool $enableDblClick = false;

    /**
     * Position the "Create" button on the right?
     *
     * @var bool
     */
    protected bool $isCreateButtonRight = false;

    /**
     * Enable DropdownAction
     *
     * @var bool
     */
    protected bool $enableDropdownAction = false;

    /**
     * Set description for following 4 action pages.
     *
     * @var array
     */
    protected array $description = [
        'index'  => 'Список',
        'show'   => 'Просмотр',
        'edit'   => 'Редактирование',
        'create' => 'Создание',
    ];

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
     * Get description.
     *
     * @return string
     */
    protected function description(): string
    {
        $method = Route::getCurrentRoute()->getActionMethod();

        return $this->description[$method] ?? trans('admin.' . str_replace('index', 'list', $method));
    }

    /**
     * Get breadcrumb.
     *
     * @param int $id
     * @return array
     */
    protected function breadcrumb(int $id = 0): array
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
        if ($this->enableStyleIndex) {
            Admin::style(self::styleIndex());
        }

        if ($this->enableDblClick) {
            self::scriptEnableDblClick();
        }

        Grid::init(function (Grid $grid) {
            $grid->disableFilter();
            $grid->disableExport();
            $grid->disablePagination();
            $grid->disableRowSelector();
            $grid->disableColumnSelector();
            $grid->tools(function(Grid\Tools $tools) {
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
            });

            if ($this->enableDropdownAction) {
                $grid->setActionClass('Encore\Admin\Grid\Displayers\DropdownActions');
            }

            if ($this->isCreateButtonRight) {
                $grid->disableCreateButton();
                $grid->tools(function(Grid\Tools $tools) {
                    $createUrl = route(str_replace('index', 'create', Route::currentRouteName()));
                    $new = trans('admin.new');
                    $tools->prepend(<<<EOT

<div class="btn-group" style="margin-right: 5px" data-toggle="buttons">
<a href="{$createUrl}" class="btn btn-sm btn-success" title="{$new}">
    <i class="fa fa-plus"></i><span class="hidden-xs">&nbsp;&nbsp;{$new}</span>
</a>
</div>

EOT
                    );
                });
            }
        });

        $content->title($this->title())
            ->description($this->description())
            ->breadcrumb(...$this->breadcrumb());

        if (method_exists($this, 'menu')) {
            $content->row($this->menu());
        }

        return $content->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function show($id, Content $content): Content
    {
        return $content
            ->title($this->title())
            ->description($this->description())
            ->breadcrumb(...$this->breadcrumb($id))
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header($this->title())
            ->description($this->description())
            ->breadcrumb(...$this->breadcrumb($id))
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content): Content
    {
        return $content
            ->header($this->title())
            ->description($this->description())
            ->breadcrumb(...$this->breadcrumb())
            ->body($this->form());
    }

    /**
     * Add fields for Show interface.
     * The field description is taken from the comments' column of the table, if any.
     *
     * @param Model $model
     * @return Show
     */
    protected function showFields(Model $model): Show
    {
        Admin::style('.col-sm-2.control-label {font-size:12px;}');

        $show = new Show($model);

        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
        });

        $columns = collect(DB::select("SHOW FULL COLUMNS FROM {$model->getTable()}"))
            ->where('Field', '<>', 'password')
            ->pluck('Comment', 'Field')
            ->toArray();

        foreach ($columns as $field => $comment) {
            $show->field($field, $comment)->unescape()->as(function($value) {
                return is_array($value) ? implode('<br>', $value) : $value;
            });
        }

        return $show;
    }

    /**
     * Get index style.
     *
     * @return string
     */
    protected static function styleIndex(): string
    {
        return <<<EOT

             /* Грид-таблица: ширина автоматическая таблицы (актуально для таблиц с малым кол-вом столбцов) */
            .table.grid-table {width:auto;}

            /* Заголовки таблицы: размер шрифта уменьшаем, фон - серый */
            .table.grid-table th {font-size: 11px; background:lightgrey;}

            /*
               Ячейки грид-таблицы:
               - более четкое обрамление ячеек таблицы;
               - запрет переноса значений ячеек на новую строку;
               - уменьшаем отступы до 4px (по дефолту 8px);
            */
            .table.grid-table th, .table.grid-table td {border:1px solid #ddd; white-space: nowrap; padding: 4px;}

            /* Выбор столбцов для отображения/скрытия: увеличиваем ширину и уменьшаем размер шрифта */
            .column-selector > ul.dropdown-menu {width: 250px; font-size: 12px;}

            /* Уменьшаем размер меток с 14 до 12 */
            label {font-size: 12px;}

            /* Модальное окно: для заголовка меняем курсор */
            .modal-header {cursor: move;}

EOT;
    }

    # Двойной клик на гриде: Переход на просмотр записи
    protected static function scriptEnableDblClick()
    {
        Admin::script('$("table tbody tr").off("dblclick").on("dblclick", function(){$.pjax({url:"/' . implode('/', request()->segments()) . '/" + $(this).data("key"), container:"#pjax-container"})});');
    }

}
