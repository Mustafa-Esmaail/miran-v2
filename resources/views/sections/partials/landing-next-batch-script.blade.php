@once('landing-v2-next-batch')
    <script>
        (() => {
            // Batches start every Sunday; count the days left in Riyadh time (UTC+3).
            const labels = @js([
                __('web.landing_v2.next_batch_today'),
                __('web.landing_v2.next_batch_tomorrow'),
                __('web.landing_v2.next_batch_in_two_days'),
                __('web.landing_v2.next_batch_next_week'),
            ]);
            const left = (7 - new Date(Date.now() + 3 * 3600e3).getUTCDay()) % 7;

            document.querySelectorAll('[data-lv2-next-batch]').forEach((element) => {
                element.textContent = labels[Math.min(left, 3)];
            });
        })();
    </script>
@endonce
