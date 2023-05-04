<ul class="nav nav-tabs nav-statuses">
    @foreach($statuses as $status => $name)
        <li>
            <a href="javascript:void(0)" data-status="{{ $status }}" class="@if(array_key_first($statuses) == $status) active @endif">
                {{ $name }}
            </a>
        </li>
    @endforeach
</ul>
