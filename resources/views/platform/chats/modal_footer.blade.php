<style>
    .modal-footer {
        border-top: 1px solid hsl(210, 14%, 83%);
    }
    form.send_form {
        display: flex;
    }
    form.send_form textarea {
        height: 38px;
        padding: 8px 15px;
        font-size: 14px;
        color: black;
        width: calc(100% - 100px);
        border: none;
        margin: 0 10px 0 0;
        border-radius: 5px;
        border: 1px solid grey;
    }
</style>

<p style="text-align: left;margin: 0 0 5px;font-size: 12px;color: gray;">
    Статус блокировки чата: <b>{{ \App\Models\Chat::LOCK_STATUSES[$chat->lock_status] }}</b>
</p>
<form class="send_form">
    {{ csrf_field() }}
    <input type="hidden" name="chat_id" value="{{ $chat->id }}">
    <input type="hidden" name="user_id" value="0">
    <textarea name="text" placeholder="Сообщение"></textarea>
    <button class="btn btn-success submit">Отправить</button>
</form>

<script>
    localStorage.setItem("messages_cnt", 0);
    let $modal_body = $('#grid-ajax-modal .modal-body');

    function reload_chat() {
        if (! $('h4 .nav-tabs li:eq(0)').hasClass('active')) return;

        $.get('/platform/_handle_renderable_?renderable=App_Platform_Controllers_ChatMessage&key={{ $chat->id }}', function (data) {
            if (localStorage.getItem("messages_cnt") == data.messages_cnt || 0) return;

            localStorage.setItem("messages_cnt", data.messages_cnt);
            $modal_body.html(data.content || '');
            $modal_body.scrollTop($modal_body[0].scrollHeight);
        });
    }
    let timer_reload_chat = setInterval(reload_chat, 10000);

    $('#grid-ajax-modal').on('hidden.bs.modal', function (e) {
        clearTimeout(timer_reload_chat);
        $.get('/platform/old/disputes/{{ $chat->id }}/clear_unread_messages_count');
        $.admin.reload();
    });

    let $chat_send_form = $(".send_form");
    $chat_send_form.find("button.submit").click(function () {
        $.ajax({
            url: "{{ route('platform.old.chats.add_message') }}",
            method: 'POST',
            data: $chat_send_form.serialize(),
            success: function (data) {
                $chat_send_form.find('textarea').val('');
                reload_chat();
            }
        });
        return false;
    });

    // клик по табам
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#chat') {
            $('#grid-ajax-modal .modal-footer').show();
        } else {
            $('#grid-ajax-modal .modal-footer').hide();
        }
    });

    $('#grid-ajax-modal .modal-footer').show();
</script>
