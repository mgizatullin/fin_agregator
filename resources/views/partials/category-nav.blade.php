@if(isset($categories) && $categories->isNotEmpty())
<div class="category-nav overflow-x-auto mb_40">
    <div class="category-item {{ request()->routeIs($indexRoute) ? 'active' : '' }}">
        <a href="{{ url_canonical(route($indexRoute)) }}">Все</a>
    </div>
    @foreach($categories as $cat)
    <div class="category-item {{ (request()->routeIs($categoryRouteName) && request()->route('slug') == $cat->slug) ? 'active' : '' }}">
        <a href="{{ url_canonical(route($categoryRouteName, $cat->slug)) }}">{{ $cat->title ?? $cat->slug }}</a>
    </div>
    @endforeach
</div>
@endif
