<?php

declare(strict_types = 1);

namespace App\Charts;

use Chartisan\PHP\Chartisan;
use ConsoleTVs\Charts\BaseChart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SampleChart extends BaseChart
{
    /**
     * Handles the HTTP request for the given chart.
     * It must always return an instance of Chartisan
     * and never a string or an array.
     */
    public function handler(Request $request): Chartisan
    {
        DB::statement("SET lc_time_names = 'ru_RU';");
        $rows = DB::select('
            SELECT DATE_FORMAT(register_date, "%d %b") AS arc_date, COUNT(id) AS cnt
            FROM orders
            WHERE register_date > DATE_ADD(CURDATE(), INTERVAL -30 DAY)
            GROUP BY register_date
        ');

        return Chartisan::build()
            ->labels(array_column($rows,'arc_date'))
            ->dataset('Кол-во', array_column($rows,'cnt'));
    }
}
