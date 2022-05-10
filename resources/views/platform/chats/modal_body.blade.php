<style>
    #grid-ajax-modal .modal-body {
        overflow: auto;
        max-height: calc(100vh - 200px);
        padding-top: 0;
    }
    #grid-ajax-modal .modal-header {
        padding: 10px 0 0 0;
        border-bottom: 0;
    }
    #grid-ajax-modal .nav {
        padding-left: 10px;
    }
    #grid-ajax-modal .nav>li>a {
        padding: 5px 10px;
        font-size: 14px;
    }
    #grid-ajax-modal button.close {
        margin-right: 6px;
    }
    #chat {
        padding: 5px 5px 0 5px;
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

    .input-group {
        padding-bottom: 5px;
    }
</style>

<div class="tab-content">
    <div class="tab-pane active" id="chat">
        @foreach ($messages as $message)
            @empty($message->user_id)
                <div class="message--user-system">
                    <time class="message__time">{{ formatDateTime($message->created_at) }}</time>
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
                    <time class="message__time">{{ formatDateTime($message->created_at) }}</time>
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
    </div>

    <div class="tab-pane" id="customer">
        <div class="row" style="padding: 15px 0;">
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">ФИО</span>
                    <input type="text" class="form-control form-control" value="{{ $chat->customer_name }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Телефон</span>
                    <input class="form-control form-control-sm" value="{{ $chat->customer_phone }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">E-Mail</span>
                    <input class="form-control form-control-sm" value="{{ $chat->customer_email }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Пол</span>
                    <input class="form-control form-control-sm" value="{{ $chat->customer_gender }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дата рождения</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->customer_birthday, false) }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Город</span>
                    <input class="form-control form-control-sm" value="{{ $chat->customer_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Карта</span>
                    <input class="form-control form-control-sm" value="{{ $chat->customer_card_number }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дата регистрации</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->customer_register_date) }}" readonly>
                </div>
            </div>
            <div class="col-sm-4" style="padding-left: 0">
                @php($photo = asset("storage/{$chat->customer_id}/user/{$chat->customer_photo}"))
                <img class="img img-thumbnail" src="{{ $photo }}">
            </div>
        </div>
        <div class="col-md-12" style="padding: 0">
            <label class="control-label">Биография/Резюме</label>
            <textarea rows="10" class="form-control form-control-sm" readonly>{!! $chat->customer_resume !!}</textarea>
        </div>
    </div>

    <div class="tab-pane" id="performer">
        <div class="row" style="padding: 15px 0;">
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">ФИО</span>
                    <input type="text" class="form-control form-control" value="{{ $chat->performer_name }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Телефон</span>
                    <input class="form-control form-control-sm" value="{{ $chat->performer_phone }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">E-Mail</span>
                    <input class="form-control form-control-sm" value="{{ $chat->performer_email }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Пол</span>
                    <input class="form-control form-control-sm" value="{{ $chat->performer_gender }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дата рождения</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->performer_birthday, false) }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Город</span>
                    <input class="form-control form-control-sm" value="{{ $chat->performer_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Карта</span>
                    <input class="form-control form-control-sm" value="{{ $chat->performer_card_number }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дата регистрации</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->performer_register_date) }}" readonly>
                </div>
            </div>
            <div class="col-sm-4" style="padding-left: 0">
                @php($user_photo = asset("storage/{$chat->performer_id}/user/{$chat->performer_photo}"))
                <img class="img img-thumbnail" src="{{ $user_photo }}">
            </div>
        </div>
        <div class="col-md-12" style="padding: 0">
            <label class="control-label">Биография/Резюме</label>
            <textarea rows="10" class="form-control form-control-sm" readonly>{!! $chat->performer_resume !!}</textarea>
        </div>
    </div>

    <div class="tab-pane" id="order">
        <div class="row" style="padding: 15px 0;">
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">Заказ № {{ $chat->order_id }}</span>
                    <input type="text" class="form-control form-control" value="{{ $chat->order_name }}" readonly>
                </div>
                <div class="input-group">
                    @php($anchor = $chat->order_url ? "<a href='{$chat->order_url}' target='_blank'><i class='fa fa-external-link'></i></a>" : '')
                    <span class="input-group-addon">Ссылка {!! $anchor !!}</span>
                    <input class="form-control form-control-sm" value="{{ $chat->order_url }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Количество</span>
                    <input class="form-control form-control-sm" value="{{ $chat->order_products_count }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Цена</span>
                    <input class="form-control form-control-sm" value="{{ $chat->order_price }} {{ $chat->order_currency }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Вознаграждение</span>
                    <input class="form-control form-control-sm" value="{{ $chat->order_profit_price }} {{ $chat->order_profit_currency }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Откуда</span>
                    <input class="form-control form-control-sm" value="{{ $chat->order_from_country }}, {{ $chat->order_from_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Куда</span>
                    <input class="form-control form-control-sm" value="{{ $chat->order_to_country }}, {{ $chat->order_to_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Создано</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->order_created_at, false) }}" title="{{ formatDateTime($chat->order_created_at) }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дедлайн</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->order_deadline, false) }}" readonly>
                </div>
            </div>
            <div class="col-sm-4" style="padding-left: 0">

                @php($images = json_decode($chat->order_images))
                <div id="order_carousel" class="carousel slide" data-ride="carousel" style="padding: 5px;border: 1px solid #f4f4f4;background-color:white;width:185px;">
                    <ol class="carousel-indicators">
                        @foreach($images as $key => $image)
                            <li data-target="#order_carousel" data-slide-to="{{$key}}" class="{{ $key == 0 ? 'active' : '' }}"></li>
                        @endforeach
                    </ol>
                    <div class="carousel-inner">
                        @foreach($images as $key => $image)
                            @php($image = asset("storage/{$chat->customer_id}/orders/{$image}"))
                            <div class="item {{ $key == 0 ? 'active' : '' }}">
                                <img src="{{ $image }}" style='max-width:185px;max-height:300px;display: block;margin-left: auto;margin-right: auto;'>
                            </div>
                        @endforeach
                    </div>
                    <a class="left carousel-control" href="#order_carousel" data-slide="prev">
                        <span class="fa fa-angle-left"></span>
                    </a>
                    <a class="right carousel-control" href="#order_carousel" data-slide="next">
                        <span class="fa fa-angle-right"></span>
                    </a>
                </div>

            </div>
        </div>
        <div class="col-md-12" style="padding: 0">
            <label class="control-label">Описание</label>
            <div class="form-control" style="height:auto;" readonly><?= $chat->order_description ?></div>
        </div>
    </div>

    <div class="tab-pane" id="route">
        <div class="row" style="padding: 15px 0;">
            <div class="col-sm-12">
                <div class="input-group">
                    <span class="input-group-addon">Статус маршрута № {{ $chat->route_id }}</span>
                    <input type="text" class="form-control form-control" value="{{ $chat->route_status }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Откуда</span>
                    <input class="form-control form-control-sm" value="{{ $chat->route_from_country }}, {{ $chat->route_from_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Куда</span>
                    <input class="form-control form-control-sm" value="{{ $chat->route_to_country }}, {{ $chat->route_to_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Создано</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->route_created_at, false) }}" title="{{ formatDateTime($chat->route_created_at) }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дедлайн</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->route_deadline, false) }}" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane" id="rate">
        <div class="row" style="padding: 15px 0;">
            <div class="col-sm-8">
                <div class="input-group">
                    <span class="input-group-addon">Статус ставки № {{ $chat->route_id }}</span>
                    <input type="text" class="form-control form-control" value="{{ $chat->route_status }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Сумма</span>
                    <input class="form-control form-control-sm" value="{{ $chat->rate_amount }} {{ $chat->rate_currency }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Откуда</span>
                    <input class="form-control form-control-sm" value="{{ $chat->route_from_country }}, {{ $chat->route_from_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Куда</span>
                    <input class="form-control form-control-sm" value="{{ $chat->route_to_country }}, {{ $chat->route_to_city }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Создано</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->route_created_at, false) }}" title="{{ formatDateTime($chat->route_created_at) }}" readonly>
                </div>
                <div class="input-group">
                    <span class="input-group-addon">Дедлайн</span>
                    <input class="form-control form-control-sm" value="{{ formatDateTime($chat->route_deadline, false) }}" readonly>
                </div>
            </div>
            <div class="col-sm-4" style="padding-left: 0">
                @isset($chat->rate_images)
                    @php($images = json_decode($chat->rate_images))
                    <div id="rate_carousel" class="carousel slide" data-ride="carousel" style="padding: 5px;border: 1px solid #f4f4f4;background-color:white;width:185px;">
                        <ol class="carousel-indicators">
                            @foreach($images as $key => $image)
                                <li data-target="#rate_carousel" data-slide-to="{{$key}}" class="{{ $key == 0 ? 'active' : '' }}"></li>
                            @endforeach
                        </ol>
                        <div class="carousel-inner">
                            @foreach($images as $key => $image)
                                @php($image = asset("storage/{$chat->performer_id}/orders/{$image}"))
                                <div class="item {{ $key == 0 ? 'active' : '' }}">
                                    <img src="{{ $image }}" style='max-width:185px;max-height:300px;display: block;margin-left: auto;margin-right: auto;'>
                                </div>
                            @endforeach
                        </div>
                        <a class="left carousel-control" href="#rate_carousel" data-slide="prev">
                            <span class="fa fa-angle-left"></span>
                        </a>
                        <a class="right carousel-control" href="#rate_carousel" data-slide="next">
                            <span class="fa fa-angle-right"></span>
                        </a>
                    </div>
                @endisset
            </div>
        </div>
        <div class="col-md-12" style="padding: 0">
            <label class="control-label">Комментарий</label>
            <div class="form-control" style="height:auto;" readonly><?= $chat->rate_comment ?></div>
        </div>

    </div>
</div>
