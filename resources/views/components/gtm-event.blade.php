@props(['event', 'data' => []])

<script>
    window.trackEvent(@json($event), @json($data));
</script>
