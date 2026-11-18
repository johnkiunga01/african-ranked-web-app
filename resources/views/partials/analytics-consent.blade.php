@if(config('analytics.enabled'))
    <section id="ar-analytics-consent" data-analytics-consent role="region" aria-labelledby="ar-analytics-title" hidden
        class="fixed bottom-3 left-3 right-3 z-[100] mx-auto max-w-xl rounded-2xl border border-stone-700 bg-stone-950 px-5 py-4 text-white shadow-2xl sm:bottom-5 sm:left-5 sm:right-5">
        <h2 id="ar-analytics-title" class="text-base font-bold">Help us improve African Ranked</h2>
        <p class="mt-2 text-sm leading-relaxed text-stone-300">With your permission, we use Google Analytics to understand visits and improve our articles. This is optional. You can change your decision at any time.</p>
        <p class="mt-2 text-xs text-stone-400">Read our <a href="/pages/privacy-policy" class="font-semibold text-amber-300 underline underline-offset-2">Privacy Policy</a>.</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="button" data-analytics-choice="reject" class="min-h-11 rounded-xl border border-stone-500 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800">Reject analytics</button>
            <button type="button" data-analytics-choice="accept" class="min-h-11 rounded-xl bg-amber-400 px-4 py-2 text-sm font-bold text-stone-950 hover:bg-amber-300">Allow analytics</button>
        </div>
    </section>
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (() => {
            'use strict';
            const id = @json(config('analytics.measurement_id'));
            const key = 'ar.analytics.consent.v1';
            const panel = document.querySelector('[data-analytics-consent]');
            let loaded = false;
            const preference = () => {
                try { return window.localStorage.getItem(key); } catch { return null; }
            };
            const remember = (choice) => {
                try { window.localStorage.setItem(key, choice); } catch { /* Respect choice for this page. */ }
            };
            const initializeAnalytics = () => {
                if (loaded) return;
                loaded = true;
                window.dataLayer = window.dataLayer || [];
                window.gtag = function () { window.dataLayer.push(arguments); };
                window.gtag('consent', 'default', {
                    analytics_storage: 'denied', ad_storage: 'denied',
                    ad_user_data: 'denied', ad_personalization: 'denied'
                });
                window.gtag('consent', 'update', { analytics_storage: 'granted' });
                window.gtag('js', new Date());
                window.gtag('config', id, { send_page_view: true });
                const tag = document.createElement('script');
                tag.async = true;
                tag.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
                document.head.appendChild(tag);
            };
            const show = () => { panel.hidden = false; };
            document.querySelectorAll('[data-analytics-settings]').forEach((button) =>
                button.addEventListener('click', show));
            panel.querySelector('[data-analytics-choice="accept"]').addEventListener('click', () => {
                remember('granted');
                panel.hidden = true;
                initializeAnalytics();
            });
            panel.querySelector('[data-analytics-choice="reject"]').addEventListener('click', () => {
                remember('denied');
                panel.hidden = true;
                if (loaded) window.location.reload();
            });
            if (preference() === 'granted') initializeAnalytics();
            else if (preference() !== 'denied') show();
        })();
    </script>
@endif
