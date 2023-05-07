<div class="modal" tabindex="-1" role="dialog" id="appoint_dispute_modal">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Назначить спор менеджеру</h4>
            </div>
            <form action="/platform/disputes/appoint">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Менеджер</label>
                            <select class="form-control" style="width: 100%;" name="admin_user_id" data-value="">
                                @foreach($users as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal" tabindex="-1" role="dialog" id="close_dispute_guilty_performer_modal">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Закрыть спор (виноват путешественник)</h4>
            </div>
            <form action="/platform/disputes/close/guilty_performer">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Причина</label>
                        <select class="form-control" style="width: 100%;" name="dispute_closed_reason_id" data-value="">
                            @foreach($closed_reasons['guilty_performer'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Детальное описание закрытия спора</label>
                        <textarea name="reason_closing_description" class="form-control" rows="5" placeholder="Введите детальное описание причины закрытия спора"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal" tabindex="-1" role="dialog" id="close_dispute_guilty_customer_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Закрыть спор (виноват заказчик)</h4>
            </div>
            <form action="/platform/disputes/close/guilty_customer">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Причина</label>
                        <select class="form-control" style="width: 100%;" name="dispute_closed_reason_id" data-value="">
                            @foreach($closed_reasons['guilty_customer'] as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Детальное описание закрытия спора</label>
                        <textarea name="reason_closing_description" class="form-control" rows="5" placeholder="Введите детальное описание причины закрытия спора"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
