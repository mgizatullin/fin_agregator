@php
    // JSON-LD целиком в PHP: ключи '@context' / '@graph' вне @php ломали Blade (директивы @…).
    $schemaJsonLd = \App\Support\SchemaOrgGraphBuilder::toJson(get_defined_vars());
@endphp
<script type="application/ld+json">
{!! $schemaJsonLd !!}
</script>
