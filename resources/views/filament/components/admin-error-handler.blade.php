{{-- Error modal overlay --}}
<div
    id="miran-error-modal"
    class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4"
    aria-modal="true"
    role="alertdialog"
    aria-labelledby="miran-error-title"
>
    {{-- Backdrop --}}
    <div
        id="miran-error-backdrop"
        class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm"
    ></div>

    {{-- Card --}}
    <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-950/10 dark:ring-white/10 overflow-hidden">

        {{-- Close button --}}
        <button
            type="button"
            onclick="miranErrorModalClose()"
            aria-label="{{ __('admin.actions.cancel') }}"
            class="absolute top-3 end-3 z-10 inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-gray-300 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Danger stripe --}}
        <div class="h-1.5 bg-gradient-to-r from-danger-600 to-danger-400"></div>

        <div class="px-6 pt-8 pb-6 text-center">

            {{-- Icon --}}
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-danger-50 dark:bg-danger-500/10 ring-8 ring-danger-50/60 dark:ring-danger-500/5 mb-5">
                <svg class="w-8 h-8 text-danger-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            {{-- Title --}}
            <h2 id="miran-error-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('admin.errors.title') }}
            </h2>

            {{-- Message --}}
            <p
                id="miran-error-message"
                class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed whitespace-pre-wrap mb-4"
            ></p>

            {{-- HTTP status badge --}}
            <div id="miran-error-status-wrap" class="hidden mb-3">
                <span
                    id="miran-error-status"
                    class="inline-flex items-center gap-1.5 rounded-full bg-danger-50 dark:bg-danger-500/10 px-3 py-1 text-xs font-medium text-danger-600 dark:text-danger-400 ring-1 ring-inset ring-danger-500/20"
                >
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span id="miran-error-status-text"></span>
                </span>
            </div>

            {{-- Error code --}}
            <div id="miran-error-code-wrap" class="hidden mb-3">
                <div class="inline-flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-4 py-2.5 font-mono text-xs">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                    </svg>
                    <span class="text-gray-400 dark:text-gray-500">{{ __('admin.errors.reference') }}:</span>
                    <span id="miran-error-code" class="text-gray-700 dark:text-gray-300 font-semibold select-all"></span>
                </div>
            </div>

            {{-- Show log toggle --}}
            <div id="miran-error-log-toggle-wrap" class="hidden">
                <button
                    type="button"
                    id="miran-error-log-toggle"
                    onclick="miranErrorLogToggle()"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline"
                >
                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    <span id="miran-error-log-toggle-text">{{ __('admin.errors.show_log') }}</span>
                </button>

                {{-- Full log data --}}
                <div id="miran-error-log-wrap" class="hidden mt-3 text-start">
                    <pre id="miran-error-log" class="max-h-56 overflow-auto rounded-lg bg-gray-900 dark:bg-black text-gray-100 text-[11px] leading-relaxed p-3 whitespace-pre-wrap break-all"></pre>
                </div>
            </div>

        </div>

        {{-- Footer actions --}}
        <div class="border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 px-6 py-4 flex gap-3">
            <button
                type="button"
                onclick="miranErrorGoBack()"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
                <svg class="w-4 h-4 rtl:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('admin.actions.go_back') }}
            </button>
            <a
                href="{{ filament()->getHomeUrl() }}"
                onclick="miranErrorModalClose()"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 hover:bg-primary-500 px-4 py-2.5 text-sm font-medium text-white transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
            >
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                {{ __('admin.actions.go_to_dashboard') }}
            </a>
        </div>

    </div>
</div>

<script>
    function miranErrorModalClose() {
        document.getElementById('miran-error-modal').classList.add('hidden')
        miranErrorLogReset()
    }

    function miranErrorGoBack() {
        miranErrorModalClose()
        history.back()
    }

    function miranErrorLogToggle() {
        const logWrap = document.getElementById('miran-error-log-wrap')
        const isHidden = logWrap.classList.toggle('hidden')
        document.getElementById('miran-error-log-toggle-text').textContent = isHidden
            ? @js(__('admin.errors.show_log'))
            : @js(__('admin.errors.hide_log'))
    }

    function miranErrorLogReset() {
        document.getElementById('miran-error-log-wrap').classList.add('hidden')
        document.getElementById('miran-error-log-toggle-text').textContent = @js(__('admin.errors.show_log'))
    }

    function miranErrorModalOpen(title, message, code, status, log) {
        document.getElementById('miran-error-title').textContent = title

        const msgEl = document.getElementById('miran-error-message')
        msgEl.textContent = message || ''
        msgEl.classList.toggle('hidden', !message)

        const statusWrap = document.getElementById('miran-error-status-wrap')
        if (status) {
            document.getElementById('miran-error-status-text').textContent = 'HTTP ' + status
            statusWrap.classList.remove('hidden')
        } else {
            statusWrap.classList.add('hidden')
        }

        const codeWrap = document.getElementById('miran-error-code-wrap')
        if (code) {
            document.getElementById('miran-error-code').textContent = code
            codeWrap.classList.remove('hidden')
        } else {
            codeWrap.classList.add('hidden')
        }

        miranErrorLogReset()
        const logToggleWrap = document.getElementById('miran-error-log-toggle-wrap')
        if (log) {
            const lines = []
            if (log.reference) lines.push('Reference: ' + log.reference)
            if (log.exception) lines.push('Exception: ' + log.exception)
            if (log.file) lines.push('File: ' + log.file)
            if (log.trace) lines.push('', 'Trace:', log.trace)
            document.getElementById('miran-error-log').textContent = lines.join('\n')
            logToggleWrap.classList.remove('hidden')
        } else {
            logToggleWrap.classList.add('hidden')
        }

        document.getElementById('miran-error-modal').classList.remove('hidden')
    }

    document.getElementById('miran-error-backdrop').addEventListener('click', miranErrorModalClose)

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') miranErrorModalClose()
    })

    document.addEventListener('livewire:init', function () {
        if (window.__miranAdminErrorHandlerRegistered) return
        window.__miranAdminErrorHandlerRegistered = true

        Livewire.hook('request', ({ payload, fail }) => {
            fail(({ status, content, preventDefault }) => {
                if (requestTargetsNotificationsComponent(payload)) return

                preventDefault()

                const errorPayload = tryParseJson(content)

                miranErrorModalOpen(
                    errorPayload?.title  || @js(__('admin.errors.title')),
                    errorPayload?.message || @js(__('admin.errors.generic')),
                    errorPayload?.reference || errorPayload?.error_code || null,
                    status,
                    errorPayload?.log || null,
                )
            })
        })
    })

    function requestTargetsNotificationsComponent(payload) {
        try {
            const parsedPayload = typeof payload === 'string' ? JSON.parse(payload) : payload
            if (!Array.isArray(parsedPayload?.components) || parsedPayload.components.length !== 1) return false
            return parsedPayload.components.some((component) => {
                const snapshot = JSON.parse(component.snapshot ?? '{}')
                return Boolean(snapshot?.data?.isFilamentNotificationsComponent)
            })
        } catch {
            return false
        }
    }

    function tryParseJson(str) {
        if (!str) return null
        try { return JSON.parse(str) } catch { return null }
    }
</script>
