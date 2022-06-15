<?php

namespace App\Platform\Exporters;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Grid\Exporters\ExcelExporter;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdminExcelExporter extends ExcelExporter implements WithHeadings, ShouldAutoSize, WithEvents, WithStrictNullComparison
{
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
