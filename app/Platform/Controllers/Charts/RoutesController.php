<?php

namespace App\Platform\Controllers\Charts;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;

class RoutesController extends Controller
{
    protected string $title = 'Маршруты';
    protected string $description = 'График';
    protected string $icon = 'fa-bar-chart';

    public function index(Content $content): Content
    {
        if (request()->isNotFilled('fromdate')) {
            return $content
                ->title($this->title())
                ->description($this->description)
                ->breadcrumb(...$this->breadcrumb())
                ->body(view('platform.charts.route.params'));
        }

        $fromdate = Carbon::createFromFormat('d.m.Y H:i:s', request('fromdate', Carbon::now()->format('d.m.Y')) . ' 00:00:00')->format('Y-m-d H:i:s');
        $tilldate = Carbon::createFromFormat('d.m.Y H:i:s', request('tilldate', Carbon::now()->format('d.m.Y')) . ' 23:59:59')->format('Y-m-d H:i:s');

        $data['subtext'] = sprintf('за период с %s по %s',
            Carbon::createFromFormat('Y-m-d H:i:s', $fromdate)->format('d.m.Y'),
            Carbon::createFromFormat('Y-m-d H:i:s', $tilldate)->format('d.m.Y')
        );

        $counties_to = DB::select("
            SELECT c.id, c.name_ru AS name, COUNT(r.id) AS cnt
            FROM routes r
            JOIN countries c ON c.id = r.to_country_id
            WHERE r.created_at BETWEEN '$fromdate' AND '$tilldate'
            GROUP BY c.id, c.name_uk
            ORDER BY cnt DESC
        ");

        if (count($counties_to) == 0) {
            return $content
                ->title($this->title())
                ->description($this->description)
                ->breadcrumb(...$this->breadcrumb())
                ->body(view('platform.charts.route.params', compact('data')));
        }

        $table['headers'] = $counties_to;
        $data['counties_to'] = "'" . implode("','", array_column($counties_to,'name')) . "'";
        $data['all_series'] = count($counties_to);

        $counters = '';
        foreach ($counties_to as $country) {
            $counters .= "SUM(IF(r.to_country_id = {$country->id}, 1, 0)) AS '{$country->name}',";
        }

        $sql = "
            SELECT
              cf.name_ru AS 'Откуда',
              {$counters}
              COUNT(r.id) AS 'Всего'
            FROM routes r
            JOIN countries cf ON cf.id = r.from_country_id
            WHERE r.created_at BETWEEN '$fromdate' AND '$tilldate'
            GROUP BY cf.name_ru
            ORDER BY COUNT(r.id)
        ";

        $table['rows'] = DB::select($sql);
        $rows = [];
        foreach ($table['rows'] as $row) {
            $rows[] = "'" . implode("','", array_values((array)$row)) . "'";
        }
        $data['rows'] = $rows;

        return $content
            ->title($this->title())
            ->description($this->description)
            ->breadcrumb(...$this->breadcrumb())
            ->row(view('platform.charts.route.params', compact('data', 'table')))
            ->body(view('platform.charts.route.index', compact('data', 'table')));
    }

    protected function title(): string
    {
        return "<i class='fa {$this->icon}'></i>&nbsp;{$this->title}";
    }

    protected function breadcrumb(): array
    {
        return [
            ['text' => 'Графики', 'icon' => 'line-chart'],
            ['text' => $this->title, 'icon' => str_replace('fa-', '', $this->icon)]
        ];
    }
}
