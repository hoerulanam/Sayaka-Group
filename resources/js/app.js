import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const panel = document.querySelector('[data-mobile-menu-panel]');
    const profileButton = document.querySelector('.profile-btn');
    const profileMenu = document.getElementById('dropdownMenu');

    if (!toggle || !panel) {
        return;
    }

    const closeMenu = () => {
        panel.hidden = true;
        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Buka menu utama');
    };

    const openMenu = () => {
        if (profileMenu) {
            profileMenu.classList.remove('active');
        }

        if (profileButton) {
            profileButton.setAttribute('aria-expanded', 'false');
        }

        panel.hidden = false;
        toggle.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Tutup menu utama');
    };

    window.closeMobileMenu = closeMenu;

    toggle.addEventListener('click', event => {
        event.stopPropagation();

        if (panel.hidden) {
            openMenu();
        } else {
            closeMenu();
        }
    });

    panel.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });

    document.addEventListener('click', event => {
        if (!panel.hidden && !panel.contains(event.target) && !toggle.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) {
            closeMenu();
            toggle.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 800 && !panel.hidden) {
            closeMenu();
        }
    });
});
