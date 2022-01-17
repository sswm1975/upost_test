<ul class="nav nav-tabs">
    @foreach($languages as $lang => $icon)
        <li role="presentation" @if($lang=='uk') style="margin-left: 20px;" @endif>
            <a href="{{ route('platform.mailings.index', ['name' => $mailing, 'lang' => $lang]) }}"
               style="padding:4px 7px; color:#000; border-width: 1px 2px 0; @if(request('lang', 'uk') == $lang) border-color: lightgray; border-bottom: 1px #edf2f7 solid; @endif"
            >
                {{ $icon }}
            </a>
        </li>
    @endforeach
</ul>
