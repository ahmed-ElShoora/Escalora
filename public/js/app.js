(() => {
    const header = document.querySelector('[data-site-header]');
    const toggle = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');

    if (header) {
        const onScroll = () => {
            header.classList.toggle('is-scrolled', window.scrollY > 18);
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    if (toggle && header && panel) {
        toggle.addEventListener('click', () => {
            const isOpen = header.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        panel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                header.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    const revealItems = document.querySelectorAll('.reveal');

    if (revealItems.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.16, rootMargin: '0px 0px -40px 0px' }
        );

        revealItems.forEach((item) => observer.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }
})();
