{{-- African Ranked standalone launch treatment. Uses the approved brand mark, never an AI recreation. --}}
<style>
    .ar-pwa-launch {
        --launch-gold: #ffbf24;
        --launch-ink: #15191c;
        position: fixed;
        inset: 0;
        z-index: 90;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        pointer-events: none;
        padding: max(44px, env(safe-area-inset-top)) 24px max(44px, env(safe-area-inset-bottom));
        background: radial-gradient(ellipse 95% 68% at 50% 50%, #fffdfb 20%, #fbfaf7 70%, #fff5e5 100%);
        color: var(--launch-ink);
        text-align: center;
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        opacity: 1;
        transition: opacity .18s ease;
    }
    .ar-pwa-launch.is-leaving { opacity: 0; }
    .ar-pwa-launch::before,
    .ar-pwa-launch::after {
        content: '';
        position: absolute;
        z-index: 0;
        width: 112vw;
        height: 28vh;
        min-height: 140px;
        pointer-events: none;
        background: linear-gradient(110deg, rgba(255,191,36,.10), rgba(255,191,36,.025));
        transform: rotate(-16deg);
        border-radius: 50%;
    }
    .ar-pwa-launch::before { top: -23vh; left: -32vw; }
    .ar-pwa-launch::after { bottom: -19vh; right: -28vw; background: linear-gradient(110deg, rgba(255,191,36,.10), rgba(255,191,36,.22)); }
    .ar-pwa-launch-dots {
        position: absolute;
        z-index: 0;
        width: 132px;
        height: 132px;
        opacity: .13;
        background-image: radial-gradient(circle, #e7a51c 2px, transparent 2.5px);
        background-size: 17px 17px;
        mask-image: linear-gradient(to bottom, black, transparent);
        pointer-events: none;
    }
    .ar-pwa-launch-dots.top { top: 0; right: 0; }
    .ar-pwa-launch-dots.bottom { bottom: 0; left: 0; transform: rotate(180deg); }
    .ar-pwa-launch-content { position: relative; z-index: 1; width: 100%; max-width: 390px; transform: translateY(-2.4vh); }
    .ar-pwa-launch-mark { display: block; width: clamp(158px, 50vw, 228px); height: auto; margin: 0 auto 26px; }
    .ar-pwa-launch-name { margin: 0; color: var(--launch-ink); font-size: clamp(28px, 7.7vw, 38px); font-weight: 800; line-height: 1.16; letter-spacing: -.045em; }
    .ar-pwa-launch-tagline { margin: 13px auto 0; max-width: 310px; color: #545557; font-size: clamp(13px, 3.5vw, 15px); font-weight: 500; line-height: 1.55; }
    .ar-pwa-launch-accent { display: block; width: 66px; height: 4px; margin: 31px auto 0; border-radius: 99px; background: var(--launch-gold); }
    @media (max-height: 560px) {
        .ar-pwa-launch-content { transform: none; }
        .ar-pwa-launch-mark { width: 130px; margin-bottom: 14px; }
        .ar-pwa-launch-accent { margin-top: 18px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .ar-pwa-launch { transition: none; }
    }
</style>
<div data-pwa-launch hidden class="ar-pwa-launch" aria-hidden="true">
    <span class="ar-pwa-launch-dots top" aria-hidden="true"></span>
    <span class="ar-pwa-launch-dots bottom" aria-hidden="true"></span>
    <div class="ar-pwa-launch-content">
        <img class="ar-pwa-launch-mark" src="/africa-mark.svg?v=dotted-20261103"
             alt="" width="353" height="372" decoding="async">
        <p class="ar-pwa-launch-name">African Ranked</p>
        <p class="ar-pwa-launch-tagline">Explore Africa through rankings, culture &amp; stories</p>
        <span class="ar-pwa-launch-accent" aria-hidden="true"></span>
    </div>
</div>
