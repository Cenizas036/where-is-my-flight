import './bootstrap';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

// ══════════════════════════════════════════════════════════
//  WIMF — Global Animations & UX Enhancements
// ══════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {

    // ── 1. GSAP Page Load: Stagger fade-up for glass cards ──
    gsap.from('.glass-card', {
        y: 40,
        opacity: 0,
        duration: 0.65,
        stagger: 0.07,
        ease: 'power3.out',
        clearProps: 'all',
    });

    // Animate headings
    gsap.from('h1, h2:not(.no-anim)', {
        y: 20,
        opacity: 0,
        duration: 0.5,
        stagger: 0.1,
        ease: 'power2.out',
        clearProps: 'all',
    });

    // ── 2. GSAP ScrollTrigger: re-animate cards as they scroll in ──
    document.querySelectorAll('.glass-card').forEach((card) => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 90%',
                toggleActions: 'play none none none',
            },
            y: 30,
            opacity: 0,
            duration: 0.55,
            ease: 'power2.out',
            clearProps: 'all',
        });
    });

    // ── 3. Nav link hover ripple effect ──
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('mouseenter', () => {
            gsap.to(link, { scale: 1.06, duration: 0.2, ease: 'power1.out' });
        });
        link.addEventListener('mouseleave', () => {
            gsap.to(link, { scale: 1, duration: 0.2, ease: 'power1.out' });
        });
    });

    // ── 4. Button press spring effect ──
    document.querySelectorAll('button[type="submit"], .btn-primary').forEach(btn => {
        btn.addEventListener('mousedown', () => gsap.to(btn, { scale: 0.95, duration: 0.1 }));
        btn.addEventListener('mouseup', () => gsap.to(btn, { scale: 1, duration: 0.2, ease: 'elastic.out(1, 0.4)' }));
    });

    // ─────────────────────────────────────────────────────────
    //  IDLE CURSOR — After 10 seconds of inactivity, the cursor
    //  transforms into a glowing blue aircraft ✈
    // ─────────────────────────────────────────────────────────
    const IDLE_TIMEOUT = 10000; // 10 seconds

    // Build the custom cursor SVG element
    const cursor = document.createElement('div');
    cursor.id = 'wimf-idle-cursor';
    cursor.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
            <defs>
                <filter id="glow">
                    <feGaussianBlur stdDeviation="3.5" result="coloredBlur"/>
                    <feMerge>
                        <feMergeNode in="coloredBlur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
            </defs>
            <!-- Contrail -->
            <ellipse cx="32" cy="54" rx="3" ry="10" fill="rgba(51,145,255,0.25)" filter="url(#glow)"/>
            <!-- Main plane body -->
            <path filter="url(#glow)" fill="#3391ff"
                d="M32 4 L20 36 L24 34 L32 56 L40 34 L44 36 Z"/>
            <!-- Wings -->
            <path filter="url(#glow)" fill="#60a5fa"
                d="M32 28 L8 38 L16 40 L32 33 L48 40 L56 38 Z"/>
            <!-- Tail -->
            <path filter="url(#glow)" fill="#60a5fa"
                d="M32 46 L22 52 L26 52 L32 50 L38 52 L42 52 Z"/>
        </svg>
    `;
    cursor.style.cssText = `
        position: fixed;
        pointer-events: none;
        z-index: 999999;
        top: 0; left: 0;
        transform: translate(-50%, -50%);
        opacity: 0;
        transition: opacity 0.4s ease;
        display: none;
    `;
    document.body.appendChild(cursor);

    let idleTimer = null;
    let cursorX = 0, cursorY = 0;
    let rafId = null;

    function moveCursor(e) {
        cursorX = e.clientX;
        cursorY = e.clientY;
    }

    function animateCursor() {
        cursor.style.left = cursorX + 'px';
        cursor.style.top  = cursorY + 'px';
        rafId = requestAnimationFrame(animateCursor);
    }

    function enterIdle() {
        document.body.style.cursor = 'none';
        cursor.style.display = 'block';
        // Small delay so display:block takes effect first
        requestAnimationFrame(() => {
            cursor.style.opacity = '1';
        });
        rafId = requestAnimationFrame(animateCursor);
        // Float animation on the cursor itself
        gsap.to(cursor, {
            y: -8,
            duration: 1.2,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut',
        });
    }

    function exitIdle() {
        document.body.style.cursor = '';
        cursor.style.opacity = '0';
        gsap.killTweensOf(cursor);
        cancelAnimationFrame(rafId);
        setTimeout(() => { cursor.style.display = 'none'; }, 400);
    }

    function resetIdleTimer() {
        if (cursor.style.opacity === '1') exitIdle();
        clearTimeout(idleTimer);
        idleTimer = setTimeout(enterIdle, IDLE_TIMEOUT);
    }

    document.addEventListener('mousemove', (e) => { moveCursor(e); resetIdleTimer(); });
    document.addEventListener('keydown',  resetIdleTimer);
    document.addEventListener('scroll',   resetIdleTimer, true);
    document.addEventListener('click',    resetIdleTimer);
    document.addEventListener('touchstart', resetIdleTimer);

    // Start the timer on load
    resetIdleTimer();
});
