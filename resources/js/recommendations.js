const progress = document.querySelector('[data-recommendation-progress]');
if (progress) {
    const label = progress.querySelector('[data-recommendation-label]');
    const delay = progress.querySelector('[data-recommendation-delay]');
    let polls = 0;
    const poll = async () => {
        if (++polls > 300) {
            delay.hidden = false;
            delay.textContent = 'This is taking longer than usual. Your article is safe; check back later.';
            return;
        }
        try {
            const response = await fetch(progress.dataset.statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('unavailable');
            const result = await response.json();
            if (!['queued', 'researching', 'writing'].includes(result.status)) { location.reload(); return; }
            label.textContent = { queued: 'Waiting to start', researching: 'Checking search research', writing: 'Preparing clearer wording and structure' }[result.status];
            if (polls > 30) { delay.hidden = false; delay.textContent = 'You can leave this page. Your result will stay with the article.'; }
            setTimeout(poll, 3000);
        } catch {
            delay.hidden = false;
            delay.textContent = 'We couldn’t refresh progress. Use Check progress, or sign in again if your session ended.';
        }
    };
    setTimeout(poll, 3000);
}
const revealSection = () => {
    if (!location.hash) return;
    const target = document.getElementById(decodeURIComponent(location.hash.slice(1)));
    let parent = target;
    while (parent) { if (parent.tagName === 'DETAILS') parent.open = true; parent = parent.parentElement; }
};
revealSection();
window.addEventListener('hashchange', revealSection);
