<ul class="nav nav-tabs nav-statuses">
    @foreach($statuses as $status => $name)
        <li>
            <a href="#" data-status="{{ $status }}">
                {{ $name }}
            </a>
        </li>
    @endforeach
</ul>
