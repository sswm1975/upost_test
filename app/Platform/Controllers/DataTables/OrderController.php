<?php

namespace App\Platform\Controllers\DataTables;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Route;

class OrderController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected string $title = 'Заказы';

    /**
     * Font Awesome icon.
     *
     * @var string
     */
    protected string $icon = 'fa-shopping-bag';

    /**
     * Breadcrumb.
     *
     * @var array
     */
    protected array $breadcrumb = [];

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
        Admin::script($this->scriptDataTable());

        $content->title($this->title())
            ->description('&nbsp;')
            ->breadcrumb(...$this->breadcrumb());

        return $content->body($this->table());
    }

    protected function table()
    {
        return view('platform.datatables.orders')->with('orders', $this->getData());
    }

    public function getData()
    {
        $orders = Order::query()
            ->with(['user', 'from_country', 'from_city', 'to_country', 'to_city', 'wait_range'])
            ->get();

        $data = [];
        foreach ($orders as $order) {
            array_push($data, (object) [
                'id' => $order->id,
                'status' => $order->status,
                'user_id' => $order->user->id,
                'user_full_name' => $order->user->full_name,
                'from_country_id' => $order->from_country->id,
                'from_country_name' => $order->from_country->name_en,
                'from_city_id' => $order->from_city->id,
                'from_city_name' => $order->from_city->name_en,
                'to_country_id' => $order->to_country->id,
                'to_country_name' => $order->to_country->name_en,
                'to_city_id' => $order->to_city->id,
                'to_city_name' => $order->to_city->name_en,
                'price' => $order->price,
                'currency' => $order->currency,
                'price_usd' => $order->price_usd,
                'user_price_usd' => $order->user_price_usd,
                'products_count' => $order->products_count,
                'deadline' => $order->deadline,
                'wait_range' => $order->wait_range->id,
                'created_at' => $order->created_at->toDateString(),
                'updated_at' => $order->updated_at->toDateString(),
                'name' => $order->name,
                'description' => $order->description,
                'strikes' => implode(',', $order->strikes),
            ]);
        }

        return $data;
    }

    protected static function scriptDataTable()
    {
        return <<<SCRIPT
            $('#orders').DataTable({
                fixedColumns: true,
                columnDefs: [
                    {targets: [17,19,20], render: DataTable.render.date()},
                    {targets: 3, width2: "150px"},
                    {targets: [3,5,7,9,11], width2: "150px"},
                    {targets: [22], width2: "350px"},
                ],
                language: {
                    procssing: "Подождите...",
                    search: "Поиск:",
                    lengthMenu: "Показать _MENU_ записей",
                    info: "Записи с _START_ до _END_ из _TOTAL_ записей",
                    infoEmpty: "Записи с 0 до 0 из 0 записей",
                    infoFiltered: "(отфильтровано из _MAX_ записей)",
                    infoPostFix: "",
                    loadingRecords: "Загрузка записей...",
                    zeroRecords: "Записи отсутствуют.",
                    emptyTable: "В таблице отсутствуют данные",
                    paginate: {
                        first: "Первая",
                        previous: "«",
                        next: "»",
                        last: "Последняя"
                    },
                    aria: {
                        sortAscending: ": активировать для сортировки столбца по возрастанию",
                        sortDescending: ": активировать для сортировки столбца по убыванию"
                    }
                }
            });

            setTimeout(function(){
                $('input[type=search]').focus();
            }, 1000);
SCRIPT;
    }


}
