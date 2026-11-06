/**
 * Site-wide motion: scroll reveals, hero parallax, a sticky top bar that
 * settles on scroll, a reading progress line and video cards that come alive
 * as they scroll into view.
 *
 * Everything is progressive: content is only hidden for reveal once this file
 * has run (`motion-ready` on <html>), and nothing runs for people who prefer
 * reduced motion apart from the video play/pause bookkeeping.
 */

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const touch = window.matchMedia('(hover: none)').matches;

// Cards and headings that rise into view. Full-screen feeds, the flip card and
// anything that already animates itself are left alone.
const REVEAL = [
    '[data-reveal]',
    'main .dv-card',
    'main .dv-reel-card',
    'main .dv-panel',
    'main .dv-title',
    'main .dv-eyebrow',
    'main .dv-category-row',
    'main article',
].join(',');

const SKIP = '.dv-magic-hero, .dv-hero, .dv-reels-screen, .dv-feed, .dv-flip, .dv-magic-rise, .dv-magic-in, [data-no-reveal]';

const revealObserver = new IntersectionObserver((entries) => {
    entries
        .filter((entry) => entry.isIntersecting)
        .forEach((entry, index) => {
            const target = entry.target;
            target.style.transitionDelay = `${Math.min(index, 6) * 70}ms`;
            target.classList.add('is-visible');
            // Drop the stagger once in, or hover transitions inherit the delay.
            setTimeout(() => target.style.transitionDelay = '', 1300);
            revealObserver.unobserve(entry.target);
        });
}, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

function registerReveals(root) {
    if (reduced) return;

    const nodes = root.matches?.(REVEAL) ? [root] : [];
    nodes.push(...(root.querySelectorAll?.(REVEAL) ?? []));

    nodes.forEach((node) => {
        if (node.dataset.revealBound || node.closest(SKIP)) return;
        node.dataset.revealBound = '1';
        node.classList.add('reveal');
        revealObserver.observe(node);
    });
}

// ── Video cards ────────────────────────────────────────────────────────────
// `data-autoplay` videos play whenever they are on screen. Hover-driven reel
// cards get the same treatment on touch screens, where hover never happens,
// by replaying their own mouseenter/mouseleave handlers.
const videoObserver = new IntersectionObserver((entries) => {
    entries.forEach(({ target, isIntersecting }) => {
        if (target.tagName === 'VIDEO') {
            isIntersecting ? target.play().catch(() => {}) : target.pause();
            return;
        }

        target.dispatchEvent(new Event(isIntersecting ? 'mouseenter' : 'mouseleave'));
    });
}, { threshold: 0.6 });

function registerVideos(root) {
    const videos = root.tagName === 'VIDEO' ? [root] : [...(root.querySelectorAll?.('video') ?? [])];

    videos.forEach((video) => {
        if (video.dataset.motionBound) return;
        video.dataset.motionBound = '1';

        const card = video.closest('.dv-reel-card, [data-video-card]');

        if (card) {
            video.addEventListener('playing', () => card.classList.add('is-playing'));
            video.addEventListener('pause', () => card.classList.remove('is-playing'));
        }

        if (video.hasAttribute('data-autoplay')) {
            if (! reduced) videoObserver.observe(video);
        } else if (touch && ! reduced && card && ! card.dataset.videoBound) {
            card.dataset.videoBound = '1';
            videoObserver.observe(card);
        }
    });
}

// ── Scroll: top bar, progress line and hero parallax ──────────────────────
let progress;
let ticking = false;

function onScroll() {
    if (ticking) return;
    ticking = true;

    requestAnimationFrame(() => {
        const y = window.scrollY;
        const max = document.documentElement.scrollHeight - window.innerHeight;

        document.documentElement.classList.toggle('is-scrolled', y > 8);

        if (progress) progress.style.transform = `scaleX(${max > 0 ? y / max : 0})`;

        if (! reduced) {
            document.querySelectorAll('.dv-hero, .dv-magic-hero, [data-parallax]').forEach((hero) => {
                const rect = hero.getBoundingClientRect();
                if (rect.bottom < 0 || rect.top > window.innerHeight) return;
                hero.style.setProperty('--parallax', `${Math.round(-rect.top * 0.3)}px`);
            });
        }

        ticking = false;
    });
}

function boot() {
    if (! reduced) document.documentElement.classList.add('motion-ready');

    if (! document.querySelector('.dv-scroll-progress')) {
        progress = document.createElement('div');
        progress.className = 'dv-scroll-progress';
        progress.setAttribute('aria-hidden', 'true');
        document.body.prepend(progress);
    }

    registerReveals(document.body);
    registerVideos(document.body);
    onScroll();
}

// Livewire swaps DOM in place, so pick up anything it adds.
new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType !== 1) return;
        registerReveals(node);
        registerVideos(node);
    }));
}).observe(document.documentElement, { childList: true, subtree: true });

window.addEventListener('scroll', onScroll, { passive: true });
window.addEventListener('resize', onScroll, { passive: true });
document.addEventListener('livewire:navigated', boot);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
