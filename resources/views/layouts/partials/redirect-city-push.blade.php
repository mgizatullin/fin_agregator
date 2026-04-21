@if(!empty($redirectToCityIfStored) && !empty($sectionBaseForRedirect))
@push('redirect-city')
<script>
(function () {
    try {
        var base = {!! json_encode($sectionBaseForRedirect) !!};
        if (!base) return;

        var STORAGE_KEY_SLUG = 'selected_city_slug';
        var slug = '';
        try { slug = (localStorage.getItem(STORAGE_KEY_SLUG) || '').trim(); } catch (e) { slug = ''; }
        if (!slug) {
            // Still expose config for city-dialog.js (it may handle later interactions).
            window.__REDIRECT_TO_CITY = { base: base, enabled: true };
            return;
        }

        var cleanBase = String(base).replace(/^\/+/, '').replace(/\/+$/, '');
        if (!cleanBase) return;

        var p = window.location.pathname || '/';
        // Normalize: ensure leading slash, collapse duplicate slashes, compare without trailing slash.
        p = ('/' + String(p)).replace(/\/+/g, '/');
        var current = p.replace(/\/+$/, '') || '/';

        var basePath = '/' + cleanBase;
        var target = basePath + '/' + slug + '/';

        // Redirect only from the base section root (e.g. /kredity or /kredity/).
        if (current === basePath) {
            window.location.replace(target);
            return;
        }

        // Expose for city-dialog.js if no redirect happened.
        window.__REDIRECT_TO_CITY = { base: base, enabled: true };
    } catch (e) {
        window.__REDIRECT_TO_CITY = { base: {!! json_encode($sectionBaseForRedirect) !!}, enabled: true };
    }
})();
</script>
@endpush
@endif
