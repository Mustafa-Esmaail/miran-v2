@props([
    'title' => null,
    'fullWidth' => false,
    'metaTitle' => null,
    'metaDescription' => null,
    'ogImageUrl' => null,
    'canonicalUrl' => null,
    'whatsappMessage' => null,
    'hideChrome' => false,
])

@include('layouts.app', [
    'title' => $title,
    'fullWidth' => $fullWidth,
    'metaTitle' => $metaTitle,
    'metaDescription' => $metaDescription,
    'ogImageUrl' => $ogImageUrl,
    'canonicalUrl' => $canonicalUrl,
    'whatsappMessage' => $whatsappMessage,
    'hideChrome' => $hideChrome,
    'slot' => $slot,
])