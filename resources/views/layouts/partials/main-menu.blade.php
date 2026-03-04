<nav class="main-menu style-default">
    <ul class="navigation">
        @foreach(($siteSettings->navigation ?? []) as $item)
            @if(!empty($item['children']))
                <li class="has-child position-relative">
                    <a href="{{ $item['url'] ?? 'javascript:void(0)' }}">{{ $item['title'] ?? '' }}</a>
                    <ul class="submenu">
                        @foreach($item['children'] as $child)
                            <li class="menu-item">
                                <a href="{{ $child['url'] ?? '#' }}">{{ $child['title'] ?? '' }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li>
                    <a href="{{ $item['url'] ?? '#' }}">{{ $item['title'] ?? '' }}</a>
                </li>
            @endif
        @endforeach
    </ul>
</nav>
