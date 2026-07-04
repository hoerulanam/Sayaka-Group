<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin - SAYAKA GROUP')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @stack('styles')
</head>
<body class="admin-layout @yield('body-class')">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <span class="brand-mark">SG</span>
                <div>
                    <h2>SAYAKA Group</h2>
                    <p>Admin Workspace</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ Route::is('admin.dashboard') ? 'active' : '' }}">
                <span class="nav-icon"><x-admin-icon name="dashboard" /></span>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('admin.news.index') }}" class="nav-link {{ Route::is('admin.news.*') ? 'active' : '' }}">
                <span class="nav-icon"><x-admin-icon name="news" /></span>
                <span>Kelola Berita</span>
            </a>

            <details class="sidebar-dropdown" {{ Route::is('admin.products.*') ? 'open' : '' }}>
                <summary class="nav-link {{ Route::is('admin.products.*') ? 'active' : '' }}">
                    <span class="nav-icon"><x-admin-icon name="products" /></span>
                    <span>Kelola Produk</span>
                </summary>
                <div class="sidebar-submenu">
                    <a href="{{ route('admin.products.brand.index', 1) }}" class="sub-link {{ Route::is('admin.products.brand.index') && request()->route('brand') == 1 ? 'active' : '' }}">SummerShoesCare</a>
                    <a href="{{ route('admin.products.brand.index', 2) }}" class="sub-link {{ Route::is('admin.products.brand.index') && request()->route('brand') == 2 ? 'active' : '' }}">SummerLabs</a>
                    <a href="{{ route('admin.products.brand.index', 3) }}" class="sub-link {{ Route::is('admin.products.brand.index') && request()->route('brand') == 3 ? 'active' : '' }}">NOID</a>
                    <a href="{{ route('admin.products.brand.index', 4) }}" class="sub-link {{ Route::is('admin.products.brand.index') && request()->route('brand') == 4 ? 'active' : '' }}">FORTEGO</a>
                </div>
            </details>

            <details class="sidebar-dropdown" {{ Route::is('admin.inventory.*') ? 'open' : '' }}>
                <summary class="nav-link {{ Route::is('admin.inventory.*') ? 'active' : '' }}">
                    <span class="nav-icon"><x-admin-icon name="inventory" /></span>
                    <span>Inventaris</span>
                </summary>
                <div class="sidebar-submenu">
                    <a href="{{ route('admin.inventory.index') }}" class="sub-link {{ Route::is('admin.inventory.index') ? 'active' : '' }}">Data Barang</a>
                    <a href="{{ route('admin.inventory.incoming') }}" class="sub-link {{ Route::is('admin.inventory.incoming') ? 'active' : '' }}">Barang Masuk</a>
                    <a href="{{ route('admin.inventory.outgoing') }}" class="sub-link {{ Route::is('admin.inventory.outgoing') ? 'active' : '' }}">Barang Keluar</a>
                    <a href="{{ route('admin.inventory.report') }}" class="sub-link {{ Route::is('admin.inventory.report') ? 'active' : '' }}">Laporan</a>
                </div>
            </details>

            <a href="{{ route('admin.contacts.index') }}" class="nav-link {{ Route::is('admin.contacts.*') ? 'active' : '' }}">
                <span class="nav-icon"><x-admin-icon name="contacts" /></span>
                <span>Kelola Pesan</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ Route::is('admin.users.*') ? 'active' : '' }}">
                <span class="nav-icon"><x-admin-icon name="users" /></span>
                <span>Kelola User</span>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="nav-link {{ Route::is('admin.orders.*') ? 'active' : '' }}">
                <span class="nav-icon"><x-admin-icon name="orders" /></span>
                <span>Kelola Pesanan</span>
            </a>
            <a href="{{ route('admin.chat.index') }}" class="nav-link {{ Route::is('admin.chat.*') ? 'active' : '' }}">
                <span class="nav-icon"><x-admin-icon name="chat" /></span>
                <span>Chat</span>
            </a>
            <a href="/" class="nav-link">
                <span class="nav-icon"><x-admin-icon name="website" /></span>
                <span>Kembali ke Website</span>
            </a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="admin-main" id="adminMain">
        <header class="admin-header">
            <div class="admin-header-title">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Tutup menu admin" aria-expanded="true" title="Tutup/buka menu admin">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div>
                    <p class="admin-title-eyebrow">Dashboard Admin</p>
                    <h1>@yield('page-title', 'Admin')</h1>
                </div>
            </div>
            <div class="admin-user">
                @auth
                    <span class="admin-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    <span class="admin-user-meta">
                        <span class="admin-user-name">{{ Auth::user()->name }}</span>
                        <span class="admin-user-role">Administrator</span>
                    </span>
                    <form action="{{ route('logout') }}" method="POST" style="display:inline;" data-confirm-title="Logout dari admin?" data-confirm-text="Sesi admin akan ditutup.">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">Logout</button>
                    </form>
                @endauth
            </div>
        </header>

        <section class="admin-content">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </section>
    </main>

    <div class="admin-loading" id="adminLoading" aria-live="polite" aria-hidden="true">
        <div class="loading-panel">
            <span class="loading-spinner"></span>
            <span>Memproses...</span>
        </div>
    </div>

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const adminMain = document.getElementById('adminMain');
            const adminLoading = document.getElementById('adminLoading');

            const showLoading = () => {
                adminLoading.classList.add('show');
                adminLoading.setAttribute('aria-hidden', 'false');
            };

            const hideLoading = () => {
                adminLoading.classList.remove('show');
                adminLoading.setAttribute('aria-hidden', 'true');
            };

            window.showAdminLoading = showLoading;
            window.hideAdminLoading = hideLoading;

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

            if (window.Swal && pageAlerts.length) {
                const popups = pageAlerts.map(alert => {
                    const icon = alertIcon(alert);

                    return {
                        icon,
                        title: alert.dataset.title || alertTitle(icon),
                        html: alert.innerHTML.trim(),
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: icon === 'error' ? '#dc2626' : '#2f6fed',
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

                pageAlerts.forEach(alert => alert.remove());
                popups.reduce((chain, popup) => chain.then(() => Swal.fire(popup)), Promise.resolve());
            } else {
                pageAlerts.forEach(alert => {
                    alert.classList.add('alert-fallback-visible');
                    alert.setAttribute('role', alert.classList.contains('alert-success') ? 'status' : 'alert');
                });
            }

            const sidebarStorageKey = 'sayaka-admin-sidebar-collapsed';
            const isMobileSidebar = () => window.matchMedia('(max-width: 960px)').matches;

            const updateSidebarToggleState = () => {
                const isExpanded = isMobileSidebar()
                    ? sidebar.classList.contains('open')
                    : !document.body.classList.contains('sidebar-collapsed');

                sidebarToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                sidebarToggle.setAttribute('aria-label', isExpanded ? 'Tutup menu admin' : 'Buka menu admin');
                sidebarToggle.setAttribute('title', isExpanded ? 'Tutup menu admin' : 'Buka menu admin');
            };

            const closeMobileSidebar = () => {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
                updateSidebarToggleState();
            };

            const applySavedSidebarState = () => {
                if (isMobileSidebar()) {
                    document.body.classList.remove('sidebar-collapsed');
                    updateSidebarToggleState();
                    return;
                }

                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
                document.body.classList.toggle(
                    'sidebar-collapsed',
                    localStorage.getItem(sidebarStorageKey) === 'true'
                );
                updateSidebarToggleState();
            };

            applySavedSidebarState();

            sidebarToggle.addEventListener('click', () => {
                if (isMobileSidebar()) {
                    sidebar.classList.toggle('open');
                    sidebarOverlay.classList.toggle('show');
                    updateSidebarToggleState();
                    return;
                }

                const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(sidebarStorageKey, isCollapsed ? 'true' : 'false');
                updateSidebarToggleState();
            });

            sidebarOverlay.addEventListener('click', () => {
                closeMobileSidebar();
            });

            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (isMobileSidebar()) {
                        closeMobileSidebar();
                    }
                });
            });

            window.addEventListener('resize', applySavedSidebarState);

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

                    const methodInput = form.querySelector('input[name="_method"]');
                    const method = (methodInput ? methodInput.value : form.method).toUpperCase();
                    const needsConfirm = method === 'DELETE' || form.dataset.confirmTitle || form.dataset.confirmText;

                    if (!needsConfirm || form.dataset.confirmed === 'true') {
                        showLoading();
                        return;
                    }

                    event.preventDefault();

                    const runSubmit = () => {
                        form.dataset.confirmed = 'true';
                        showLoading();
                        form.submit();
                    };

                    if (!window.Swal) {
                        if (window.confirm(form.dataset.confirmText || 'Lanjutkan aksi ini?')) {
                            runSubmit();
                        }
                        return;
                    }

                    Swal.fire({
                        icon: method === 'DELETE' ? 'warning' : 'question',
                        title: form.dataset.confirmTitle || (method === 'DELETE' ? 'Hapus data ini?' : 'Lanjutkan aksi?'),
                        text: form.dataset.confirmText || (method === 'DELETE' ? 'Data yang dihapus tidak dapat dikembalikan.' : 'Pastikan data sudah benar.'),
                        showCancelButton: true,
                        confirmButtonText: method === 'DELETE' ? 'Ya, hapus' : 'Ya, lanjutkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: method === 'DELETE' ? '#dc2626' : '#2f6fed',
                        cancelButtonColor: '#64748b'
                    }).then(result => {
                        if (result.isConfirmed) {
                            runSubmit();
                        }
                    });
                });
            });

            document.querySelectorAll('.sidebar a, .admin-table a, .admin-actions a').forEach(link => {
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
                        link.hasAttribute('download')
                    ) {
                        return;
                    }

                    showLoading();
                });
            });

            window.addEventListener('pageshow', hideLoading);
        });
    </script>
</body>
</html>
