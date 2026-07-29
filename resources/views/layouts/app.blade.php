<!-- filepath: c:\MyFiles\sayaka-group\resources\views\layouts\app.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Website Company Profile SAYAKA GROUP')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site-layout">
    <header class="main-header">
        <div class="main-header-inner">
            <a href="{{ route('home') }}" class="main-brand" aria-label="SAYAKA GROUP">
                <img src="{{ asset('images/logo_sayaka.jpg') }}" alt="SAYAKA GROUP" decoding="async" fetchpriority="high" />
                <span>
                    <strong>SAYAKA GROUP</strong>
                    <small>Official multi brand store</small>
                </span>
            </a>

            <nav class="main-nav desktop-main-nav" aria-label="Navigasi utama">
                @include('layouts.partials.customer-navigation')
            </nav>

            <div class="main-actions">
                <form action="{{ route('search') }}" method="get" class="header-search desktop-header-search">
                    <label for="header-search-input" class="sr-only">Cari produk, brand, atau berita</label>
                    <input id="header-search-input" type="search" name="q" placeholder="Cari produk..." value="{{ request('q') }}">
                    <button type="submit">Cari</button>
                </form>

                @auth
                    <a href="{{ route('cart.index') }}" class="header-pill" aria-label="Keranjang">
                        <span class="header-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20 8H6" />
                                <circle cx="10" cy="19" r="1.25" />
                                <circle cx="18" cy="19" r="1.25" />
                            </svg>
                        </span>
                        <span class="header-action-label">Keranjang</span>
                    </a>
                @endauth

                <div class="profile-dropdown">
                    <button class="profile-btn" type="button" onclick="toggleDropdown()" title="Akun" aria-haspopup="true" aria-expanded="false">
                        <span class="header-action-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="8" r="3.25" />
                                <path d="M5.5 20a6.5 6.5 0 0 1 13 0" />
                            </svg>
                        </span>
                        <span class="header-action-label">Akun</span>
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        @auth
                            <div class="user-name">{{ auth()->user()->name }}</div>
                            <a href="{{ route('profile.edit') }}">Profil Saya</a>
                            <a href="{{ route('chat.index') }}">Chat</a>
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.news.index') }}">Admin</a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST" data-confirm-title="Logout dari akun?" data-confirm-text="Sesi Anda akan ditutup.">
                                @csrf
                                <button type="submit">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}">Login</a>
                            <a href="{{ route('register') }}">Register</a>
                        @endauth
                    </div>
                </div>

                <button
                    class="mobile-menu-toggle"
                    type="button"
                    aria-label="Buka menu utama"
                    aria-controls="mobileMenuPanel"
                    aria-expanded="false"
                    data-mobile-menu-toggle
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
            </div>

            <div class="mobile-menu-panel" id="mobileMenuPanel" data-mobile-menu-panel hidden>
                <form action="{{ route('search') }}" method="get" class="mobile-header-search">
                    <label for="mobile-header-search-input" class="sr-only">Cari produk, brand, atau berita</label>
                    <input id="mobile-header-search-input" type="search" name="q" placeholder="Cari produk..." value="{{ request('q') }}">
                    <button type="submit">Cari</button>
                </form>

                <nav class="mobile-main-nav" aria-label="Navigasi utama mobile">
                    @include('layouts.partials.customer-navigation')
                </nav>
            </div>
        </div>

        <script>
            function toggleDropdown() {
                if (typeof window.closeMobileMenu === 'function') {
                    window.closeMobileMenu();
                }

                const menu = document.getElementById('dropdownMenu');
                const button = document.querySelector('.profile-btn');
                const isOpen = menu.classList.toggle('active');
                button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            }

            document.addEventListener('click', function(event) {
                const dropdown = document.querySelector('.profile-dropdown');
                if (!dropdown.contains(event.target)) {
                    document.getElementById('dropdownMenu').classList.remove('active');
                    document.querySelector('.profile-btn').setAttribute('aria-expanded', 'false');
                }
            });
        </script>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} PT Kreasi Produk Nusantara. Semua hak cipta dilindungi.</p>
        <p>Jl. Betung Raya No.270, RT.7/RW.8, Pd. Bambu,<br>
        Kec. Duren Sawit, Kota Jakarta Timur, DKI Jakarta 13430</p>
    </footer>

    <div class="site-loading" id="siteLoading" aria-live="polite" aria-hidden="true">
        <div class="site-loading-panel">
            <span class="site-loading-spinner"></span>
            <span>Memproses...</span>
        </div>
    </div>

    @stack('modals')

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const siteLoading = document.getElementById('siteLoading');
            let loadingTimer = null;
            let sweetAlertPromise = null;

            const showLoading = () => {
                window.clearTimeout(loadingTimer);
                siteLoading.classList.add('show');
                siteLoading.setAttribute('aria-hidden', 'false');
            };

            const hideLoading = () => {
                window.clearTimeout(loadingTimer);
                siteLoading.classList.remove('show');
                siteLoading.setAttribute('aria-hidden', 'true');
            };

            const scheduleLoading = () => {
                window.clearTimeout(loadingTimer);
                loadingTimer = window.setTimeout(showLoading, 350);
            };

            const loadSweetAlert = () => {
                if (window.Swal) {
                    return Promise.resolve(window.Swal);
                }

                if (!sweetAlertPromise) {
                    sweetAlertPromise = new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                        script.async = true;
                        script.onload = () => resolve(window.Swal);
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }

                return sweetAlertPromise;
            };

            window.showSiteLoading = showLoading;
            window.hideSiteLoading = hideLoading;
            window.scheduleSiteLoading = scheduleLoading;
            window.loadSweetAlert = loadSweetAlert;

            const cartScrollStorageKey = 'sayaka:cart-scroll-restore';
            const currentScrollUrl = () => window.location.origin + window.location.pathname + window.location.search;

            window.rememberCartClickPosition = (form, trigger = null) => {
                if (!form) {
                    return;
                }

                try {
                    window.sessionStorage.setItem(cartScrollStorageKey, JSON.stringify({
                        url: currentScrollUrl(),
                        scrollY: window.scrollY || window.pageYOffset || 0,
                        formId: form.id || null,
                        triggerText: trigger?.textContent?.trim() || null,
                        timestamp: Date.now()
                    }));
                } catch (error) {
                    // Browser storage may be unavailable; cart flow should continue normally.
                }
            };

            const restoreCartClickPosition = () => {
                let restoreState = null;

                try {
                    const rawState = window.sessionStorage.getItem(cartScrollStorageKey);

                    if (!rawState) {
                        return;
                    }

                    restoreState = JSON.parse(rawState);
                    window.sessionStorage.removeItem(cartScrollStorageKey);
                } catch (error) {
                    try {
                        window.sessionStorage.removeItem(cartScrollStorageKey);
                    } catch (removeError) {
                        // Ignore unavailable storage.
                    }

                    return;
                }

                if (
                    !restoreState ||
                    restoreState.url !== currentScrollUrl() ||
                    Date.now() - Number(restoreState.timestamp || 0) > 5 * 60 * 1000
                ) {
                    return;
                }

                if ('scrollRestoration' in window.history) {
                    window.history.scrollRestoration = 'manual';
                }

                const target = restoreState.formId ? document.getElementById(restoreState.formId) : null;
                const restore = () => {
                    if (target && typeof target.scrollIntoView === 'function') {
                        target.scrollIntoView({ block: 'center', inline: 'nearest' });
                        return;
                    }

                    window.scrollTo({
                        top: Number(restoreState.scrollY || 0),
                        left: 0,
                        behavior: 'auto'
                    });
                };

                window.requestAnimationFrame(restore);
                window.setTimeout(restore, 160);
                window.setTimeout(() => {
                    if ('scrollRestoration' in window.history) {
                        window.history.scrollRestoration = 'auto';
                    }
                }, 500);
            };

            restoreCartClickPosition();

            const alertIcon = alert => {
                if (alert.classList.contains('alert-success')) {
                    return 'success';
                }

                if (alert.classList.contains('alert-error') || alert.classList.contains('alert-danger')) {
                    return 'error';
                }

                if (alert.classList.contains('alert-warning')) {
                    return 'warning';
                }

                return 'info';
            };

            const alertTitle = icon => {
                const titles = {
                    success: 'Berhasil',
                    error: 'Terjadi Kesalahan',
                    warning: 'Periksa Kembali',
                    info: 'Informasi'
                };

                return titles[icon] || titles.info;
            };

            const pageAlerts = Array.from(document.querySelectorAll('.alert'));

            if (pageAlerts.length) {
                const popups = pageAlerts.map(alert => {
                    const icon = alertIcon(alert);

                    return {
                        icon,
                        title: alert.dataset.title || alertTitle(icon),
                        html: alert.innerHTML.trim(),
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: icon === 'error' ? '#dc2626' : '#2563eb',
                        timer: icon === 'success' ? 2600 : undefined,
                        timerProgressBar: icon === 'success',
                        customClass: {
                            popup: 'sayaka-swal-popup',
                            title: 'sayaka-swal-title',
                            htmlContainer: 'sayaka-swal-text',
                            confirmButton: 'sayaka-swal-confirm'
                        }
                    };
                });

                loadSweetAlert()
                    .then(Swal => {
                        pageAlerts.forEach(alert => alert.remove());
                        popups.reduce((chain, popup) => chain.then(() => Swal.fire(popup)), Promise.resolve());
                    })
                    .catch(() => {
                        pageAlerts.forEach(alert => {
                            alert.classList.add('alert-fallback-visible');
                            alert.setAttribute('role', alert.classList.contains('alert-success') ? 'status' : 'alert');
                        });
                    });
            } else {
                pageAlerts.forEach(alert => {
                    alert.classList.add('alert-fallback-visible');
                    alert.setAttribute('role', alert.classList.contains('alert-success') ? 'status' : 'alert');
                });
            }

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function (event) {
                    if (event.defaultPrevented) {
                        return;
                    }

                    if (form.dataset.noLoading === 'true') {
                        return;
                    }

                    if (!form.checkValidity()) {
                        return;
                    }

                    const submitter = event.submitter || document.activeElement;

                    if (submitter?.dataset?.preserveScroll === 'cart-add') {
                        window.rememberCartClickPosition(form, submitter);
                    }

                    const methodInput = form.querySelector('input[name="_method"]');
                    const method = (methodInput ? methodInput.value : form.method).toUpperCase();
                    const needsConfirm = method === 'DELETE' || form.dataset.confirmTitle || form.dataset.confirmText;

                    if (!needsConfirm || form.dataset.confirmed === 'true') {
                        scheduleLoading();
                        return;
                    }

                    event.preventDefault();

                    const submitForm = () => {
                        form.dataset.confirmed = 'true';
                        showLoading();
                        form.submit();
                    };

                    loadSweetAlert()
                        .then(Swal => Swal.fire({
                            icon: form.dataset.confirmIcon || (method === 'DELETE' ? 'warning' : (form.dataset.confirmTitle ? 'warning' : 'question')),
                            title: form.dataset.confirmTitle || (method === 'DELETE' ? 'Hapus data ini?' : 'Lanjutkan aksi?'),
                            text: form.dataset.confirmText || (method === 'DELETE' ? 'Data yang dihapus tidak dapat dikembalikan.' : 'Pastikan data sudah benar.'),
                            showCancelButton: true,
                            confirmButtonText: form.dataset.confirmButton || (method === 'DELETE' ? 'Ya, hapus' : 'Ya, batalkan'),
                            cancelButtonText: form.dataset.cancelButton || 'Batal',
                            confirmButtonColor: form.dataset.confirmColor || (method === 'DELETE' || form.dataset.confirmTitle?.includes('Batalkan') ? '#dc2626' : '#2563eb'),
                            cancelButtonColor: '#64748b',
                            customClass: {
                                popup: 'sayaka-swal-popup',
                                title: 'sayaka-swal-title',
                                htmlContainer: 'sayaka-swal-text',
                                confirmButton: 'sayaka-swal-confirm'
                            }
                        }))
                        .then(result => {
                            if (result.isConfirmed) {
                                submitForm();
                            }
                        })
                        .catch(() => {
                            if (window.confirm(form.dataset.confirmText || 'Lanjutkan aksi ini?')) {
                                submitForm();
                            }
                        });
                });
            });

            document.querySelectorAll('a[href]').forEach(link => {
                link.addEventListener('click', function (event) {
                    const href = link.getAttribute('href');
                    const target = link.getAttribute('target');

                    if (
                        event.defaultPrevented ||
                        event.button !== 0 ||
                        event.metaKey ||
                        event.ctrlKey ||
                        event.shiftKey ||
                        event.altKey ||
                        !href ||
                        href.startsWith('#') ||
                        href.startsWith('mailto:') ||
                        href.startsWith('tel:') ||
                        target === '_blank' ||
                        link.hasAttribute('download') ||
                        link.dataset.noLoading === 'true'
                    ) {
                        return;
                    }

                    scheduleLoading();
                });
            });

            window.addEventListener('pageshow', hideLoading);
        });
    </script>
</body>
</html>
