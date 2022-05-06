<style>
    .modal-body {
        overflow: auto;
        height: calc(100vh - 200px);
        background: #F4F4F4;
    }
    .chat {
        padding: 0 5px;
    }
    .message {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
    }
    .message--user-customer + .message--user-performer,
    .message--user-performer + .message--user-customer {
        margin-top: 1em;
    }
    .message--user-customer + .message--user-customer,
    .message--user-performer + .message--user-performer {
        margin-top: .5em;
    }
    .message__time {
        font-size: 12px;
        color: #aaa;
        width: 100%;
        margin: 0 0 0 60px;
    }
    .message__author-pic {
        margin: 0 10px 0 0;
    }
    .message__author-pic img {
        height: 50px;
        width: 50px;
        border-radius: 50%;
    }
    .message__text {
        padding: 10px;
        border-radius: 10px;
        border: 1px solid #69b4f3;
        background-color: #bfe2ff;
        max-width: 85%;
    }
    .message__text p {
        margin: 0;
    }
    .message--user-performer {
        justify-content: flex-end;
    }
    .message--user-performer .message__time {
        text-align: right;
        margin: 0 60px 0 0;
    }
    .message--user-performer .message__author-pic {
        order: 1;
        margin: 0 0 0 10px;
    }
    .message--user-performer .message__text {
        background-color: #69b4f3;
    }
    .message--user-system, .message--user-admin {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        margin: 10px 0;
    }
    .message--user-system img {
        border-radius: 0;
    }
    .message--user-system .message__text {
        border: 1px solid darkred;
        background-color: orange;
        color:white;
    }
    .message--user-admin .message__text {
        border: 1px solid darkred;
        background-color: crimson;
        color:white;
    }
    .is_dispute_message {
        border: 2px solid red;
    }
    .attachfiles {
        display: inline-flex;
        flex-flow: row;
        flex-wrap: wrap;
    }
    .attachfiles a {
        width: 50px;
        height: 50px;
        margin: 10px 10px 0 0;
        border: 1px solid #CEE7EF;
        border-radius: 5px;
        overflow: hidden;
    }
    .attachfiles a img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<section class="chat">
    @foreach ($messages as $message)
        @empty($message->user_id)
            <div class="message--user-system">
                <time class="message__time">{{ $message->created_at }}</time>
                <figure class="message__author-pic" title="Системное сообщение">
                    <img src="/img/bullhorn.png">
                </figure>
                <div class="message__text">
                    <p>{{ $message->text }}</p>
                </div>
            </div>
        @else
            @php
                if ($message->user->role == 'admin') {
                    $user = 'admin';
                }  else {
                    $user = $message->user_id == $chat->customer_id ? 'customer' : 'performer';
                }
                $user_name = $message->user->full_name;
                $user_photo = $message->user->photo ?: '/img/empty-user.png';
            @endphp
            <div class="message message--user-{{ $user }}">
                <time class="message__time">{{ $message->created_at }}</time>
                <figure class="message__author-pic" title="{{ $user_name }}">
                    <img src="{{ $user_photo }}">
                </figure>
                <div class="message__text {{ $message->is_dispute_message ? 'is_dispute_message' : ''}}">
                    <p>{{ $message->text }}</p>
                    @forelse($message->images_thumb as $image)
                        <div class="attachfiles">
                            <a href="{{ $message->images_original[$loop->index] }}" class="attach-image" target="_blank">
                                <img src="{{ $image }}">
                            </a>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        @endif
    @endforeach
</section>
