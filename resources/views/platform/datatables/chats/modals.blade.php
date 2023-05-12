<div class="modal" tabindex="-1" role="dialog" id="chat_lock_status_modal">
    <div class="modal-dialog " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Установить статус блокировки для чата</h4>
            </div>
            <form action="/platform/chats/set_chat_lock_status">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Статус</label>
                        <select class="form-control" style="width: 100%;" name="chat_lock_status" data-value="">
                            @foreach($data['chat_lock_status'] as $id => $name)
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
