<?php

namespace App\Platform\Exporters;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid\Exporters\ExcelExporter;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ClientExcelExporter extends ExcelExporter implements WithHeadings, ShouldAutoSize, WithEvents, WithStrictNullComparison
{
    /**
     * @var string
     */
    protected $fileName = 'Клиенты.xlsx';

    /**
     * @var array
     */
    protected $columns = [
        'id'            => 'Код',
        'surname'       => 'Фамилия',
        'name'          => 'Имя',
        'phone'         => 'Телефон',
        'email'         => 'Емейл',
        'gender'        => 'Пол',
        'birthday'      => 'Дата рождения',
        'city_name'     => 'Город',
        'wallet'        => 'Баланс',
        'currency'      => 'Валюта',
        'lang'          => 'Язык',
        'card_number'   => '№ карточки',
        'card_name'     => 'Имя на карте',
        'status'        => 'Статус',
        'validation'    => 'Валидация',
        'scores_count'  => 'К-во баллов',
        'reviews_count' => 'К-во отзывов',
        'rating'        => 'Рейтинг',
        'failed_delivery_count' => 'К-во неуд.доставок',
        'failed_receive_count'  => 'К-во неуд.получений',
        'last_active'   => 'Последняя активность',
        'register_date' => 'Дата регистрации',
        'created_at'    => 'Добавлено',
        'updated_at'    => 'Изменено',
    ];

    public function query()
    {
        return parent::query()
            ->withoutAppends()
            ->join('cities', 'cities.id', 'users.city_id')
            ->select([
                'users.id',
                'users.name',
                'users.surname',
                DB::raw('CONCAT(" ", users.phone) AS phone'),
                'users.email',
                'users.gender',
                'users.birthday',
                'cities.name_ru AS city_name',
                'users.wallet',
                'users.currency',
                'users.lang',
                DB::raw('CONCAT(" ", users.card_number) AS card_number'),
                'users.card_name',
                'users.status',
                'users.validation',
                'users.scores_count',
                'users.reviews_count',
                DB::raw('IF(users.reviews_count > 0, ROUND(users.scores_count / users.reviews_count, 2), 0) AS rating'),
                'users.failed_delivery_count',
                'users.failed_receive_count',
                'users.last_active',
                'users.register_date',
                'users.created_at',
                'users.updated_at',
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
