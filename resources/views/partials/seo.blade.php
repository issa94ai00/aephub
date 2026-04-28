@php
    $canonical = url('/');
    $siteName = trim((string) ($site['site_name_resolved'] ?? '')) !== ''
        ? trim((string) $site['site_name_resolved'])
        : ($site['site_name'] ?? config('app.name'));
    $pageTitle = trim((string) ($site['page_title'] ?? '')) !== ''
        ? trim((string) $site['page_title'])
        : $siteName;
    $description = trim((string) ($site['seo_meta_description_resolved'] ?? ''));
    if ($description === '') {
        $description = __('site.seo.default_description');
    }
    if (trim((string) ($seoCanonical ?? '')) !== '') {
        $canonical = trim((string) $seoCanonical);
    }
    if (trim((string) ($seoPageTitle ?? '')) !== '') {
        $pageTitle = trim((string) $seoPageTitle);
    }
    if (trim((string) ($seoMetaDescription ?? '')) !== '') {
        $description = trim((string) $seoMetaDescription);
    }
    $keywords = trim((string) ($site['seo_keywords_resolved'] ?? ''));
    $ogImage = trim((string) ($site['seo_og_image'] ?? ''));
    $locale = str_replace('_', '-', app()->getLocale());
    $ogLocale = $locale === 'ar' ? 'ar_AR' : 'en_US';
@endphp

<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $description }}">
@if($keywords !== '')
    <meta name="keywords" content="{{ $keywords }}">
@endif
<meta name="author" content="{{ $siteName }}">
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $ogLocale }}">
@if($ogImage !== '')
    <meta property="og:image" content="{{ $ogImage }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $description }}">
@if($ogImage !== '')
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif

@php
    $sameAs = array_filter([
        $site['facebook_url'] ?? null,
        $site['telegram_url'] ?? null,
    ]);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'EducationalOrganization',
        'name' => $siteName,
        'url' => $canonical,
        'description' => $description !== '' ? $description : null,
    ];
    if (! empty($site['contact_email'])) {
        $schema['email'] = $site['contact_email'];
    }
    if (! empty($site['contact_phone'])) {
        $schema['telephone'] = $site['contact_phone'];
    }
    if ($sameAs !== []) {
        $schema['sameAs'] = array_values($sameAs);
    }
    $schema = array_filter($schema, fn ($v) => $v !== null && $v !== '');
@endphp
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
