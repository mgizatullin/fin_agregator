@if(isset($categories) && $categories->isNotEmpty())
<div class="category-nav overflow-x-auto">
    <div class="category-item {{ request()->routeIs($indexRoute) ? 'active' : '' }}">
        <a href="{{ route($indexRoute) }}">Все</a>
    </div>
    @foreach($categories as $cat)
    <div class="category-item {{ (request()->routeIs($categoryRouteName) && request()->route('slug') == $cat->slug) ? 'active' : '' }}">
        <a href="{{ route($categoryRouteName, $cat->slug) }}">{{ $cat->title ?? $cat->slug }}</a>
    </div>
    @endforeach
</div>
@endif
