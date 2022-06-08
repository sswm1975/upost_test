<ul class="nav nav-tabs nav-statuses">
    @foreach($statuses as $status => $item)
        <li>
            <a href="{{ route(request()->route()->getName(), compact('status')) }}" class="@if(request('status', array_key_first($statuses)) == $status) active @endif">
                {{ $item->name }} @if(!empty($item->count))<span class="label label-{{ $item->color ?? 'default' }}">{{ $item->count }}</span>@endif
            </a>
        </li>
    @endforeach
</ul>
