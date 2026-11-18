/* Progressive enhancement; works independently of analytics/GA4 consent. */
(() => {
    'use strict';
    const root = document.querySelector('[data-pwa-root]');
    if (!root || !window.isSecureContext) return;

    const launch = root.querySelector('[data-pwa-open]');
    const panel = root.querySelector('[data-pwa-panel]');
    const action = root.querySelector('[data-pwa-install]');
    const close = root.querySelector('[data-pwa-close]');
    const help = root.querySelector('[data-pwa-help]');
    const feedback = root.querySelector('[data-pwa-feedback]');
    const ua = navigator.userAgent || '';
    const isIOS = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isAndroid = /Android/i.test(ua);
    const platform = isIOS ? 'ios' : isAndroid ? 'android' : 'desktop';
    const installed = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const mobile = isIOS || isAndroid;
    let installEvent = null;

    // No user identity, user agent, IP, cookie ID, or client token is sent to the server.
    const send = async (event) => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrf) return false;
        try {
            const response = await fetch('/pwa/telemetry', {
                method: 'POST', credentials: 'same-origin', keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ event, platform })
            });
            return response.ok;
        } catch {
            return false;
        }
    };

    // A local flag keeps normal refreshes from inflating first-open counts.
    const recordFirstOpen = async () => {
        const key = 'ar.pwa.first-standalone-launch.v1';
        try { if (localStorage.getItem(key)) return; } catch { /* Storage may be unavailable. */ }
        if (await send('standalone_first_open')) {
            try { localStorage.setItem(key, '1'); } catch { /* Private browsing or storage restrictions. */ }
        }
    };

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // The normal website remains fully functional without service worker support.
        });
    }
    if (installed()) {
        recordFirstOpen();
        return;
    }
    if (!mobile) return;

    const show = () => {
        panel.hidden = false;
        launch.hidden = true;
        feedback.textContent = '';
        if (isIOS) {
            action.hidden = true;
            help.textContent = 'On iPhone or iPad: open the browser Share menu, select Add to Home Screen, then tap Add. Launch the new African Ranked icon to use it like an app.';
        } else if (installEvent) {
            action.hidden = false;
            action.textContent = 'Install African Ranked';
            help.textContent = 'Add the African Ranked app to your Android home screen.';
        } else {
            action.hidden = true;
            help.textContent = 'On Android, open the browser menu (⋮), choose Install app or Add to Home screen, and follow the prompts.';
        }
        send('help_opened');
    };

    launch.hidden = false;
    launch.addEventListener('click', show);
    close.addEventListener('click', () => {
        panel.hidden = true;
        launch.hidden = false;
    });
    action.addEventListener('click', async () => {
        if (!installEvent) return;
        const prompt = installEvent;
        installEvent = null;
        try {
            await prompt.prompt();
            const response = await prompt.userChoice;
            if (response?.outcome === 'accepted') {
                send('prompt_accepted');
                feedback.textContent = 'Installation accepted. Find African Ranked on your home screen.';
            } else {
                feedback.textContent = 'You can install later from your browser menu.';
            }
        } catch {
            feedback.textContent = 'Use your browser menu to install African Ranked.';
        }
        action.hidden = true;
    });

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installEvent = event;
        if (!panel.hidden && !isIOS) {
            action.hidden = false;
            action.textContent = 'Install African Ranked';
            help.textContent = 'Add the African Ranked app to your Android home screen.';
        }
    });
    window.addEventListener('appinstalled', () => {
        if (!isIOS) send('installed');
        panel.hidden = true;
        launch.hidden = true;
    });
})();
