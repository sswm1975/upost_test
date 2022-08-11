<div id="report_params-modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="modalReportParamsLabel" aria-hidden="false" style="display: none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title" id="modalReportParamsLabel"><i class="fa fa-gears"></i>&nbsp;&nbsp;Параметры формирования отчета</h4>
            </div>

            <div class="modal-body">
                <form pjax-container>
                    <div class="form">
                        <div class="form-group">
                            <div class="input-group input-group">
                                <span class="input-group-addon"><strong>Период</strong></span>
                                <input type="text" class="form-control" id="fromdate" placeholder="Дата С" name="fromdate" value="{{ request('fromdate') }}">
                                <span class="input-group-addon" style="border-left: 0; border-right: 0;">-</span>
                                <input type="text" class="form-control" id="tilldate" placeholder="Дата По" name="tilldate" value="{{ request('tilldate') }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-success submit">Сформировать</button>
            </div>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <div class="pull-left">
            <div class="btn-group">
                <a href="#report_params-modal" class="btn btn-warning btn-sm" data-toggle="modal">
                    <i class="fa fa-check-square-o" title="Подготовить данные"></i><span class="hidden-xs">&nbsp;&nbsp;Подготовить данные</span>
                </a>
            </div>
        </div>
        <div class="pull-right">
        </div>
    </div>
    <div class="box-body">
        @isset($data['all_series'])
            <div id="chart" class="pull-left" style="width: 70%;height:600px;"></div>
            <div class="pull-right">
                <table class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th></th>
                            @foreach($table['headers'] as $row)
                                <th>{!! $row->name !!}</th>
                            @endforeach
                            <th>Всего</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_reverse($table['rows']) as $row)
                            <tr><th>{!! implode('<td align="center">', array_values((array)$row)) !!}
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            @isset($data)
            <div class="alert alert-danger alert-dismissable">
                <h4><i class="icon fa fa-ban"></i>Нет данных {{ $data['subtext'] }}</h4>
            </div>
            @endempty
        @endisset
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // выбор периода для даты
        $('#fromdate').datetimepicker({"format":"DD.MM.YYYY","locale":"ru","useCurrent":true});
        $('#tilldate').datetimepicker({"format":"DD.MM.YYYY","locale":"ru","useCurrent":true});
        $("#fromdate").on("dp.change", function (e) {
            $('#tilldate').data("DateTimePicker").minDate(e.date);
        });
        $("#tilldate").on("dp.change", function (e) {
            $('#fromdate').data("DateTimePicker").maxDate(e.date);
        });

        // нажата кнопка "Подготовить данные"
        $("#report_params-modal .submit").click(function () {
            $("#report_params-modal").modal('toggle');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();

            $.pjax({
                url: window.location.origin + window.location.pathname + '?'+ $('#report_params-modal form').serialize(),
                container: '#pjax-container'
            })
        });
    });
</script>
