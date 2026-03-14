@if(!empty($redirectToCityIfStored) && !empty($sectionBaseForRedirect))
@push('redirect-city')
<script>window.__REDIRECT_TO_CITY = { base: {!! json_encode($sectionBaseForRedirect) !!}, enabled: true };</script>
@endpush
@endif
