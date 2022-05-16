<style>
    .box.grid-box {border-top: 0;}
    .nav-statuses li:first-child {margin-left: 20px;}
    .nav-statuses li a {padding:4px 7px; color:#000; border-width: 1px 2px 0;}
    .nav-statuses li a.active {border-color: lightgray; background: white; border-bottom: 1px solid white;}
    .nav-statuses .label {padding: 0.2em 0.4em 0.2em;}
</style>

<ul class="nav nav-tabs nav-statuses">
    @foreach($statuses as $status => $item)
        <li>
            <a href="{{ route('platform.disputes.index', compact('status')) }}" class="@if(request('status', array_key_first($statuses)) == $status) active @endif">
                {{ $item['name'] }} @if($item['count'])<span class="label label-success">{{ $item['count'] }}</span>@endif
            </a>
        </li>
    @endforeach
</ul>
