import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

function initSwipers(root) {
    const SwiperClass = window.Swiper;
    if (typeof SwiperClass !== 'function') {
        return;
    }

    const catalogSwiper = root.querySelector('[data-landing-catalog-swiper]');
    if (catalogSwiper) {
        new SwiperClass(catalogSwiper, {
            slidesPerView: 1.1,
            spaceBetween: 12,
            breakpoints: {
                640: {
                    slidesPerView: 2.1,
                    spaceBetween: 16,
                },
                1024: {
                    slidesPerView: 3.1,
                    spaceBetween: 18,
                },
            },
            pagination: {
                el: root.querySelector('[data-landing-catalog-pagination]'),
                clickable: true,
            },
            navigation: {
                prevEl: root.querySelector('[data-landing-catalog-prev]'),
                nextEl: root.querySelector('[data-landing-catalog-next]'),
            },
        });
    }
}

function resetLandingState(root) {
    root.querySelectorAll('[data-landing-stagger], [data-landing-fade], [data-landing-reveal]').forEach((node) => {
        node.style.opacity = '1';
        node.style.transform = 'none';
    });
}

function initLandingMotion(root) {
    gsap.registerPlugin(ScrollTrigger);

    const staggerNodes = root.querySelectorAll('[data-landing-stagger]');
    if (staggerNodes.length > 0) {
        gsap.from(staggerNodes, {
            opacity: 0,
            y: 28,
            duration: 0.85,
            ease: 'power2.out',
            stagger: 0.12,
        });
    }

    const fadeNodes = root.querySelectorAll('[data-landing-fade]');
    if (fadeNodes.length > 0) {
        gsap.from(fadeNodes, {
            opacity: 0,
            y: 14,
            duration: 0.7,
            ease: 'power2.out',
        });
    }

    const logo = root.querySelector('[data-landing-logo]');
    if (logo) {
        gsap.from(logo, {
            opacity: 0,
            scale: 0.75,
            duration: 1,
            ease: 'power3.out',
            delay: 0.15,
        });
    }

    const rings = root.querySelectorAll('[data-landing-logo-ring]');
    rings.forEach((ring, index) => {
        gsap.to(ring, {
            rotate: index % 2 === 0 ? 360 : -360,
            duration: index % 2 === 0 ? 40 : 55,
            repeat: -1,
            ease: 'none',
            transformOrigin: '50% 50%',
        });
    });

    root.querySelectorAll('[data-landing-reveal]').forEach((section) => {
        gsap.from(section, {
            y: 36,
            opacity: 0,
            duration: 0.8,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: section,
                start: 'top 85%',
            },
        });
    });

    root.querySelectorAll('[data-landing-parallax]').forEach((layer) => {
        const speed = Number(layer.getAttribute('data-landing-parallax') ?? '0.1');
        gsap.to(layer, {
            yPercent: speed * 100,
            ease: 'none',
            scrollTrigger: {
                trigger: root,
                scrub: true,
                start: 'top top',
                end: 'bottom top',
            },
        });
    });
}

function initCardTilt(root) {
    root.querySelectorAll('[data-landing-card]').forEach((card) => {
        let rafId = 0;

        const applyTilt = (event) => {
            const rect = card.getBoundingClientRect();
            const relativeX = event.clientX - rect.left;
            const relativeY = event.clientY - rect.top;
            const rotateY = ((relativeX / rect.width) - 0.5) * 8;
            const rotateX = (0.5 - (relativeY / rect.height)) * 8;
            card.style.transform = `perspective(900px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-2px)`;
            card.style.boxShadow = '0 12px 30px rgba(15, 23, 42, 0.15)';
        };

        card.addEventListener('mousemove', (event) => {
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            rafId = requestAnimationFrame(() => applyTilt(event));
        });

        card.addEventListener('mouseleave', () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
            }
            card.style.transform = '';
            card.style.boxShadow = '';
        });
    });
}

function initMusicLanding() {
    const root = document.querySelector('[data-music-landing]');
    if (!root) {
        return;
    }

    initSwipers(root);

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion) {
        resetLandingState(root);
        return;
    }

    initLandingMotion(root);
    initCardTilt(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMusicLanding);
} else {
    initMusicLanding();
}
