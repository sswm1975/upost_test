<?php

namespace App\Platform\Exporters;

use App\Models\Order;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid\Exporters\ExcelExporter as parentAlias;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OrderExcelExporter extends parentAlias implements WithHeadings, ShouldAutoSize, WithEvents, WithStrictNullComparison
{
    /**
     * @var string
     */
    protected $fileName = 'Заказы.xlsx';

    /**
     * @var array
     */
    protected $columns = [
        'id'                => 'Код',
        'user_id'           => 'Код киента',
        'user_name'         => 'Фамилия и Имя клиента',
        'name'              => 'Наименование товара',
        'slug'              => 'Слаг',
        'product_link'      => 'Ссылка на товар',
        'shop_slug'         => 'Магазин',
        'products_count'    => 'Кол-во',
        'price'             => 'Цена',
        'currency'          => 'Валюта',
        'price_usd'         => 'Цена в $',
        'user_price'        => 'Доход',
        'user_currency'     => 'Валюта',
        'user_price_usd'    => 'Доход в $',
        'not_more_price'    => 'Выше не принимать',
        'from_country_id'   => 'Страна откуда (код)',
        'from_country_name' => 'Страна откуда',
        'from_city_id'      => 'Город откуда (код)',
        'from_city_name'    => 'Город откуда',
        'to_country_id'     => 'Страна куда (код)',
        'to_country_name'   => 'Страна куда',
        'to_city_id'        => 'Город куда (код)',
        'to_city_name'      => 'Город куда',
        'register_date'     => 'Зарегистрирован',
        'wait_range_name'   => 'Готов ждать',
        'deadline'          => 'Дата дедлайна',
        'looks'             => 'Просмотров',
        'strikes'           => 'Жалобы',
        'status'            => 'Статус',
        'created_at'        => 'Добавлено',
        'updated_at'        => 'Изменено',
    ];

    public function query()
    {
        return parent::query()
            ->withoutAppends()
            ->select([
                'id',
                'user_id',
                DB::raw('(SELECT CONCAT(surname, " ", name) FROM users WHERE users.id = orders.user_id) AS user_name'),
                'name',
                'slug',
                'product_link',
                'shop_slug',
                'products_count',
                'price',
                'currency',
                'price_usd',
                'user_price',
                'user_currency',
                'user_price_usd',
                DB::raw('IF(not_more_price = 1, "Да", "Нет") AS not_more_price'),
                'from_country_id',
                DB::raw('(SELECT name_ru FROM countries WHERE countries.id = orders.from_country_id) AS from_country_name'),
                'from_city_id',
                DB::raw('(SELECT name_ru FROM cities WHERE cities.id = orders.from_city_id) AS from_city_name'),
                'to_country_id',
                DB::raw('(SELECT name_ru FROM countries WHERE countries.id = orders.to_country_id) AS to_country_name'),
                'to_city_id',
                DB::raw('(SELECT name_ru FROM cities WHERE cities.id = orders.to_city_id) AS to_city_name'),
                'register_date',
                DB::raw('(SELECT name_ru FROM wait_ranges WHERE wait_ranges.id = orders.wait_range_id) AS wait_range_name'),
                'deadline',
                'looks',
                'strikes',
                'status',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class  => function(AfterSheet $event) {
                $all_columns = count($this->columns);
                $letter = $this->getColumnNameFromNumber($all_columns);

                $ws = $event->sheet->getDelegate();
                $ws->getParent()->getProperties()
                    ->setCompany('UPost')
                    ->setTitle('')
                    ->setSubject('')
                    ->setDescription('')
                    ->setCreator(Admin::user()->surname . ' ' . Admin::user()->name);

                $ws->setShowGridlines(false);

                $ws->insertNewRowBefore(2);
                $ws->setAutoFilter('A2:' . $letter .'2');
                for ($i=1; $i <= $all_columns; $i++) {
                    $ws->setCellValueByColumnAndRow($i, 2, $i);
                }
                $ws->getStyle('A1:' . $letter .'2')->applyFromArray(
                    [
                        'font' => [
                            'bold' => true,
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'quotePrefix'    => false
                    ]
                );

                $ws->getStyle('A1:' . $letter .'1')->getFill()->setFillType(Fill::FILL_SOLID);
                $ws->getStyle('A1:' . $letter .'1')->getFill()->getStartColor()->setRGB('EEEEEE');

                $ws->getStyle('A2:' . $letter .'2')->getFill()->setFillType(Fill::FILL_SOLID);
                $ws->getStyle('A2:' . $letter .'2')->getFill()->getStartColor()->setRGB('FDE9D9');

                $ws->getStyle('A1:' . $letter . $ws->getHighestRow())->applyFromArray(
                    [
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]
                );

                $ws->freezePane('A3');

                $ws->getRowDimension(1)->setRowHeight(30);
                $ws->getTabColor()->setRGB('FF0000');

                $ws->setSelectedCell('A1');
            },
        ];
    }

    /**
     * По номеру возвращает имя столбца в Excel
     *
     * @param int $num
     * @return string
     */
    protected function getColumnNameFromNumber(int $num): string
    {
        if ($num <= 0) return '';

        $letter = '';

        while ($num){
           $p = ($num - 1) % 26;
           $num = intval(($num - $p) / 26);
           $letter = chr(65 + $p) . $letter;
        }

        return $letter;
    }
}
