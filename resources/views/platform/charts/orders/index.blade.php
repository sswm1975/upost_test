<script type="text/javascript">
    $(function () {
        let myChart = echarts.init(document.getElementById('chart'));

        let option = {
            title: {
                text: 'Маршруты',
                subtext: '{{ $data['subtext'] }}',
                left: 'center'
            },
            grid: {
                left: '3%',
                right: '10%',
                bottom: '3%',
                containLabel: true
            },
            toolbox: {
                show: true,
                feature: {
                    saveAsImage: {
                        title: 'Save as image',
                    }
                }
            },
            legend: {
                type: 'scroll',
                orient: 'vertical',
                right: 0,
                top: 55,
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            xAxis: {
                type: 'value',
                boundaryGap: [0, 0.01],
            },
            yAxis: {
                type: 'category'
            },
            dataset: {
                source: [
                    ['countries', {!! $data['counties_to'] !!}],
                    @foreach($data['rows'] as $row)
                        [{!! $row !!}],
                    @endforeach
                ]
            },
            series: [
                @for($i=0;$i<$data['all_series'];$i++)
                    {type: 'bar'},
                @endfor
            ]
        };

        myChart.setOption(option);
    });
</script>

