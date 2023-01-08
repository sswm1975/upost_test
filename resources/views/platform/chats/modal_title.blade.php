<style>
    li i.fa {margin-right:5px;}
</style>
<ul class="nav nav-tabs" id="nav-chat">
    <li class="active">
        <a href="#chat" data-toggle="tab" class="active">
            <i class="fa fa-comments-o"></i>Чат
        </a>
    </li>
    @if($exists_dispute)
        <li>
            <a href="#dispute" data-toggle="tab">
                <i class="fa fa-gavel"></i>Спор
            </a>
        </li>
    @endif
    <li>
        <a href="#customer" data-toggle="tab">
            <i class="fa fa-user-o"></i>Заказчик
        </a>
    </li>
    <li>
        <a href="#performer" data-toggle="tab">
            <i class="fa fa-user"></i>Исполнитель
        </a>
    </li>
    <li>
        <a href="#order" data-toggle="tab">
            <i class="fa fa-shopping-bag"></i>Заказ
        </a>
    </li>
    <li>
        <a href="#route" data-toggle="tab">
            <i class="fa fa-location-arrow"></i>Маршрут
        </a>
    </li>
    @if ($exists_rate)
        <li>
            <a href="#rate" data-toggle="tab">
                <i class="fa fa-handshake-o"></i>Ставка
            </a>
        </li>
    @endif
</ul>
