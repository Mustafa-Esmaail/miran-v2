import './bootstrap';
import './gtm';

window.miranSlider = function (desktopPerView, autoPlayMs) {
    desktopPerView = desktopPerView || 3;
    autoPlayMs = autoPlayMs !== undefined ? autoPlayMs : 4000;
    return {
        active: 0,
        perView: 1,
        _realTotal: 0,
        _timer: null,
        init() {
            this._setup();
            this._bindDrag();
            window.addEventListener('resize', () => this._updatePerView());
            if (autoPlayMs > 0) {
                this._timer = setInterval(() => this.next(), autoPlayMs);
            }
        },
        destroy() {
            clearInterval(this._timer);
            this._ro?.disconnect();
        },
        _setup() {
            const track = this.$refs.track;
            if (!track) return;
            this._realTotal = track.children.length;
            track.style.transition = 'none';
            this._updatePerView();
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (track) track.style.transition = '';
            }));
            const container = track.parentElement;
            if (container && container.offsetWidth === 0) {
                const ro = new ResizeObserver(() => {
                    if (container.offsetWidth > 0) {
                        ro.disconnect();
                        track.style.transition = 'none';
                        this._updatePerView();
                        requestAnimationFrame(() => requestAnimationFrame(() => {
                            if (track) track.style.transition = '';
                        }));
                    }
                });
                ro.observe(container);
                this._ro = ro;
            }
        },
        get _max() {
            return Math.max(0, this._realTotal - this.perView);
        },
        get _page() {
            return this.active;
        },
        get _dots() {
            return Array.from({ length: this._max + 1 }, (_, i) => i);
        },
        _updatePerView() {
            const w = window.innerWidth;
            if (w >= 1024) this.perView = desktopPerView;
            else if (w >= 640) this.perView = Math.min(2, desktopPerView);
            else this.perView = 1;
            this.active = Math.min(this.active, this._max);
            this._apply();
        },
        _apply() {
            const track = this.$refs.track;
            if (!track || !track.parentElement) return;
            const containerW = track.parentElement.offsetWidth;
            if (!containerW) return;
            const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            const itemW = Math.floor((containerW - (this.perView - 1) * gap) / this.perView);
            Array.from(track.children).forEach((el) => {
                el.style.minWidth = itemW + 'px';
                el.style.maxWidth = itemW + 'px';
                el.style.width = itemW + 'px';
            });
            const offset = this.active * (itemW + gap);
            const isRtl = document.documentElement.dir === 'rtl';
            track.style.transform = 'translateX(' + (isRtl ? offset : -offset) + 'px)';
        },
        prev() {
            if (this.active > 0) {
                this.active--;
                this._apply();
                this._resetTimer();
            }
        },
        next() {
            if (this.active < this._max) {
                this.active++;
                this._apply();
                this._resetTimer();
            } else {
                clearInterval(this._timer);
                this._timer = null;
            }
        },
        _resetTimer() {
            clearInterval(this._timer);
            if (autoPlayMs > 0 && this.active < this._max) {
                this._timer = setInterval(() => this.next(), autoPlayMs);
            }
        },
        _bindDrag() {
            const container = this.$refs.track?.parentElement;
            if (!container) return;
            let startX = 0;
            let dragging = false;
            let moved = false;

            const onStart = (x) => { startX = x; dragging = true; moved = false; };
            const onEnd = (x) => {
                if (!dragging) return;
                dragging = false;
                container.style.cursor = 'grab';
                const dx = x - startX;
                if (Math.abs(dx) < 40) return;
                const isRtl = document.documentElement.dir === 'rtl';
                (isRtl ? dx > 0 : dx < 0) ? this.next() : this.prev();
            };

            container.addEventListener('mousedown', (e) => {
                onStart(e.clientX);
                container.style.cursor = 'grabbing';
            });
            window.addEventListener('mousemove', (e) => {
                if (dragging && Math.abs(e.clientX - startX) > 5) moved = true;
            });
            window.addEventListener('mouseup', (e) => onEnd(e.clientX));
            container.addEventListener('touchstart', (e) => onStart(e.touches[0].clientX), { passive: true });
            window.addEventListener('touchend', (e) => onEnd(e.changedTouches[0].clientX), { passive: true });

            container.addEventListener('click', (e) => {
                if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
            }, true);

            container.style.cursor = 'grab';
            this.$refs.track.style.userSelect = 'none';
        },
    };
};

window.__MIRAN_CHAT_DEBUG = true;

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-url]');
    if (!button) {
        const actionButton = event.target.closest('[data-feedback-on], [data-feedback-off], [data-feedback]');
        if (actionButton) {
            showHeroFeedback(actionButton);
        }
        return;
    }

    const url = button.getAttribute('data-copy-url');
    if (!url) {
        return;
    }

    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(url);
        } else {
            fallbackCopy(url);
        }

        button.classList.add('is-copied');
        setTimeout(() => button.classList.remove('is-copied'), 1200);
        showHeroFeedback(button);
        showToast(button.getAttribute('data-toast') || 'Link copied');
    } catch (error) {
        console.error('Copy failed', error);
    }
});

window.addEventListener('toast', (event) => {
    const message = event?.detail?.message ?? event?.detail ?? null;
    if (message) {
        showToast(message);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    hidePageLoader();

    if (window.__initialToast) {
        showToast(window.__initialToast);
        window.__initialToast = null;
    }
});

document.addEventListener('livewire:init', () => {
    if (!window.Livewire) {
        return;
    }

    logChatDebug('livewire:init', {
        enabled: window.__MIRAN_CHAT_DEBUG === true,
    });

    Livewire.hook('request', ({ url, options, payload, respond, succeed, fail }) => {
        if (isChatPayload(payload)) {
            logChatDebug('request:start', {
                url,
                method: options?.method ?? null,
                payload,
            });
        }

        const finalize = () => {
            hidePageLoader();
        };

        respond(finalize);
        succeed(({ status, json }) => {
            finalize();

            if (isChatPayload(payload)) {
                logChatDebug('request:success', {
                    status,
                    payload,
                    json,
                });
            }
        });
        fail(({ status, response }) => {
            finalize();
            console.warn('Livewire request failed', { status, response });

            if (isChatPayload(payload)) {
                logChatDebug('request:fail', {
                    status,
                    payload,
                    response,
                });
            }
        });
    });
});

document.addEventListener('click', (event) => {
    const action = event.target.closest('[data-chat-action]');
    if (!action) {
        return;
    }

    logChatDebug('dom:click', {
        action: action.getAttribute('data-chat-action'),
        roomId: action.getAttribute('data-chat-room-id'),
        wireClick: action.getAttribute('wire:click'),
    });
}, true);

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-chat-form]');
    if (!form) {
        return;
    }

    const textarea = form.querySelector('textarea');

    logChatDebug('dom:submit', {
        form: form.getAttribute('data-chat-form'),
        wireSubmit: form.getAttribute('wire:submit') ?? form.getAttribute('wire:submit.prevent'),
        bodyLength: textarea?.value?.length ?? 0,
        bodyPreview: textarea?.value?.slice(0, 120) ?? '',
    });
}, true);

window.addEventListener('error', (event) => {
    if (!window.__MIRAN_CHAT_DEBUG) {
        return;
    }

    logChatDebug('window:error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
    });
});

window.addEventListener('unhandledrejection', (event) => {
    if (!window.__MIRAN_CHAT_DEBUG) {
        return;
    }

    logChatDebug('window:unhandledrejection', {
        reason: String(event.reason),
    });
});

window.addEventListener('livewire-upload-start', (event) => {
    console.log('Livewire upload started', event.detail);
});

window.addEventListener('livewire-upload-progress', (event) => {
    console.log('Livewire upload progress', event.detail);
});

window.addEventListener('livewire-upload-finish', (event) => {
    console.log('Livewire upload finished', event.detail);
});

window.addEventListener('livewire-upload-error', (event) => {
    console.error('Livewire upload error', event.detail);
});

window.addEventListener('livewire-upload-cancel', (event) => {
    console.warn('Livewire upload canceled', event.detail);
});

window.addEventListener('livewire:navigated', () => {
    hidePageLoader();
});

window.addEventListener('pageshow', () => {
    hidePageLoader();
});

window.addEventListener('beforeunload', () => {
    showPageLoader();
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a');
    if (!link) {
        return;
    }

    if (!shouldShowLoaderForLink(link)) {
        return;
    }

    showPageLoader();
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form || form.hasAttribute('data-no-loader')) {
        return;
    }

    if (hasLivewireSubmit(form)) {
        return;
    }

    showPageLoader();
});

function showHeroFeedback(button) {
    const container = button.closest('.course-hero-actions');
    if (!container) {
        return;
    }

    const feedbackEl = container.querySelector('.course-hero-feedback');
    if (!feedbackEl) {
        return;
    }

    let message = button.getAttribute('data-feedback');
    if (!message) {
        const pressed = button.getAttribute('aria-pressed') === 'true';
        const on = button.getAttribute('data-feedback-on');
        const off = button.getAttribute('data-feedback-off');
        message = pressed ? off : on;
    }

    if (!message) {
        return;
    }

    feedbackEl.textContent = message;
    feedbackEl.classList.add('is-visible');
    clearTimeout(feedbackEl._hideTimer);
    feedbackEl._hideTimer = setTimeout(() => {
        feedbackEl.classList.remove('is-visible');
    }, 1400);
}

function showToast(message) {
    const root = document.getElementById('toast-root');
    const messageEl = document.getElementById('toast-message');
    if (!root || !messageEl) {
        return;
    }

    messageEl.textContent = message;
    root.classList.add('is-visible');

    clearTimeout(root._hideTimer);
    root._hideTimer = setTimeout(() => {
        root.classList.remove('is-visible');
    }, 1800);
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', 'readonly');
    textarea.style.position = 'absolute';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}

function showPageLoader() {
    const loader = document.getElementById('page-loader');
    if (!loader) {
        return;
    }

    loader.classList.add('is-visible');
    loader.setAttribute('aria-busy', 'true');
}

function hidePageLoader() {
    const loader = document.getElementById('page-loader');
    if (!loader) {
        return;
    }

    loader.classList.remove('is-visible');
    loader.setAttribute('aria-busy', 'false');
}

function shouldShowLoaderForLink(link) {
    if (link.target && link.target !== '_self') {
        return false;
    }

    if (link.hasAttribute('download') || link.hasAttribute('data-no-loader')) {
        return false;
    }

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#')) {
        return false;
    }

    try {
        const url = new URL(href, window.location.href);
        if (url.origin !== window.location.origin) {
            return false;
        }

        if (url.pathname === window.location.pathname && url.search === window.location.search) {
            return false;
        }
    } catch (error) {
        return false;
    }

    return true;
}

function hasLivewireSubmit(form) {
    return Array.from(form.attributes).some((attr) => attr.name.startsWith('wire:submit'));
}

function logChatDebug(label, payload = {}) {
    if (!window.__MIRAN_CHAT_DEBUG) {
        return;
    }

    const now = new Date().toISOString();
    console.groupCollapsed(`[chat-debug] ${label} @ ${now}`);
    console.log(payload);
    console.trace();
    console.groupEnd();
}

function isChatPayload(payload) {
    if (!payload) {
        return false;
    }

    try {
        const serialized = JSON.stringify(payload);

        return serialized.includes('chat.')
            || serialized.includes('send')
            || serialized.includes('openRoom')
            || serialized.includes('showRooms')
            || serialized.includes('closeChat')
            || serialized.includes('room-selected')
            || serialized.includes('MessageThread')
            || serialized.includes('FloatingChat');
    } catch (error) {
        return false;
    }
}
