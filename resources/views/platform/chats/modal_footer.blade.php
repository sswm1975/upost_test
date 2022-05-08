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

<form class="send_form">
    {{ csrf_field() }}
    <input type="hidden" name="chat_id" value="{{ $chat->id }}">
    <input type="hidden" name="user_id" value="{{ Admin::user()->user_id }}">
    <textarea name="text" placeholder="Сообщение"></textarea>
    <button class="btn btn-success submit">Отправить</button>
</form>

<script>
    function reload_chat() {
        let $modal_body = $('#grid-ajax-modal .modal-body');
        let prev_content = $modal_body.html();
        $.get('/platform/_handle_renderable_?renderable=App_Platform_Controllers_ChatMessage&key={{ $chat->id }}', function (data) {
            let content = data.content || '';
            if (prev_content == content) return;
            $modal_body.html(content);
            $modal_body.scrollTop($modal_body[0].scrollHeight);
        });
    }
    let timer_reload_char = setInterval(reload_chat, 10000);

    $('#grid-ajax-modal').on('hidden.bs.modal', function (e) {
        clearTimeout(timer_reload_char);
    })

    let $chat_send_form = $(".send_form");
    $chat_send_form.find("button.submit").click(function () {
        $.ajax({
            url: "{{ route('platform.chats.add_message') }}",
            method: 'POST',
            data: $chat_send_form.serialize(),
            success: function (data) {
                $chat_send_form.find('textarea').val('');
                reload_chat();
            }
        });
        return false;
    });
</script>
