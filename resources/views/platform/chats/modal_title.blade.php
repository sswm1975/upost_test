<ul class="nav nav-tabs" id="nav-chat">
    <li class="active">
        <a href="#chat" data-toggle="tab" class="active">
            <i class="fa fa-comments-o"></i>&nbsp;Чат
        </a>
    </li>
    @if($exists_dispute)
        <li>
            <a href="#dispute" data-toggle="tab">
                <i class="fa fa-gavel"></i>&nbsp;Спор
            </a>
        </li>
    @endif
    <li>
        <a href="#customer" data-toggle="tab">
            <i class="fa fa-user-o"></i>&nbsp;Заказчик
        </a>
    </li>
    <li>
        <a href="#performer" data-toggle="tab">
            <i class="fa fa-user"></i>&nbsp;Исполнитель
        </a>
    </li>
    <li>
        <a href="#order" data-toggle="tab">
            <i class="fa fa-shopping-bag"></i>&nbsp;Заказ
        </a>
    </li>
    <li>
        <a href="#route" data-toggle="tab">
            <i class="fa fa-location-arrow"></i>&nbsp;Маршрут
        </a>
    </li>
    <li>
        <a href="#rate" data-toggle="tab">
            <i class="fa fa-handshake-o"></i>&nbsp;Ставка
        </a>
    </li>
</ul>
