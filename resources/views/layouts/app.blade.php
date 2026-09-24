@if(request()->ajax() && request()->query('inventrack_section') === '1')
    <div class="inventrack-section-fragment"
        data-title="@yield('title', 'Dashboard')"
        data-subtitle="@yield('subtitle', '')">
        @yield('content')
    </div>
    @stack('scripts')
@else
@php
    $sectionMap = [
        'dashboard' => 'dashboardSection',
        'items.*' => 'itemsSection',
        'transactions.*' => 'transactionsSection',
        'stock.*' => 'stockSection',
        'reports.*' => 'reportsSection',
        'users.*' => 'usersSection',
        'pendingUsers.*' => 'usersSection',
        'stock-requests.*' => 'stockRequestsSection',
        'stuff-requests.*' => 'stuffRequestsSection',
        'import.*' => 'importSection',
    ];

    $currentSectionId = 'dashboardSection';
    foreach ($sectionMap as $routePattern => $sectionId) {
        if (request()->routeIs($routePattern)) {
            $currentSectionId = $sectionId;
            break;
        }
    }

    $isTeknikContext = auth()->check() && (auth()->user()->isTeknik() || (auth()->user()->isSuperAdmin() && request('sa_bidang') === 'teknik'));
    if (request()->routeIs('transactions.*') && $isTeknikContext) {
        $currentSectionId = request('type') === 'out' ? 'transactionsIssueSection' : 'transactionsReceiptSection';
    }
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Nextlog</title>
    <meta name="description" content="InvenTrack - Sistem Manajemen Inventory Modern">

    {{-- Open Graph / link preview (WhatsApp, dll) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NextLog">
    <meta property="og:title" content="@yield('title', 'Dashboard') - NextLog">
    <meta property="og:description" content="@yield('subtitle', 'InvenTrack - Sistem Manajemen Inventory Modern')">
    <meta property="og:image" content="{{ asset('images/favicon-512.png') }}">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Dashboard') - NextLog">
    <meta name="twitter:description" content="@yield('subtitle', 'InvenTrack - Sistem Manajemen Inventory Modern')">
    <meta name="twitter:image" content="{{ asset('images/favicon-512.png') }}">

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preload" as="image" href="{{ asset('images/logo-web-top.png') }}" fetchpriority="high">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Google Fonts Roboto Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('images/favicon-48.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon-192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#198754">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="NextLog">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <!-- Icon Teknik -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Prevent flash: apply theme before render -->
    <script>
        (function () {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>

<body>
    <!-- Animated Nexus Background -->
    <div class="background-glow-container">
        <div class="nexus-bg"></div>
        <div class="nexus-grid"></div>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
        $isTeknikManager = auth()->user()->isManager() && $isTeknik;
        $canUseAdminMenus = auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || $isTeknikManager;
    @endphp
    <aside class="sidebar collapsed{{ $isTeknik ? ' sidebar-teknik' : '' }}" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-wrapper">
                <div class="brand-icon">
                    <div class="sidebar-logo-stack">
                        <img src="{{ asset('images/logo-web-top.png') }}" alt="InvenTrack"
                            class="sidebar-logo-part sidebar-logo-top" decoding="async" fetchpriority="high">
                        <img src="{{ asset('images/logo-web-bottom.png') }}" alt=""
                            class="sidebar-logo-part sidebar-logo-bottom" loading="lazy" decoding="async">
                    </div>
                </div>
                <div class="logo-container">
                    <div class="next-logistic">
                        NEXT LOGISTIC
                    </div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu Utama</div>

            {{-- Dashboard: Admin & Manager only --}}
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin() || auth()->user()->isManager())
                <a href="{{ route('dashboard') }}"
                    onclick="switchSection('dashboardSection', this); return false;"
                    data-section="dashboardSection"
                    class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="{{ $isTeknik ? 'fa-solid fa-chart-pie' : 'bi bi-grid-1x2-fill' }}"></i>
                    <span>{{ $isTeknik ? 'Overview' : 'Dashboard' }}</span>
                    @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                        @php $pendingTxCount = \App\Models\Transaction::visibleFor(auth()->user())->pending()->count(); @endphp
                        @if($pendingTxCount > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingTxCount }}</span>
                        @endif
                    @endif
                </a>
            @endif

            {{-- Master Barang: Admin only --}}
            @if($canUseAdminMenus)
                <a href="{{ route('items.index') }}"
                    onclick="switchSection('itemsSection', this); return false;"
                    data-section="itemsSection"
                    class="sidebar-link {{ request()->routeIs('items.*') ? 'active' : '' }}">
                    <i class="{{ $isTeknik ? 'fa-solid fa-cubes-stacked' : 'bi bi-box-fill' }}"></i>
                    <span>{{ $isTeknik ? 'Master SOH' : 'Master Barang' }}</span>
                </a>
            @endif

            {{-- Transaksi: Admin & Staff only --}}
            @if($canUseAdminMenus || auth()->user()->isStaff())
                @if($isTeknik)
                    <a href="{{ route('transactions.index', ['type' => 'in']) }}"
                        onclick="switchSection('transactionsReceiptSection', this); return false;"
                        data-section="transactionsReceiptSection"
                        class="sidebar-link {{ request()->routeIs('transactions.*') && request('type', 'in') === 'in' ? 'active' : '' }}">
                        <i class="{{ $isTeknik ? 'fa-solid fa-box-open' : 'bi bi-box-arrow-in-down' }}"></i>
                        <span>Goods Receipt</span>
                    </a>
                    <a href="{{ route('transactions.index', ['type' => 'out']) }}"
                        onclick="switchSection('transactionsIssueSection', this); return false;"
                        data-section="transactionsIssueSection"
                        class="sidebar-link {{ request()->routeIs('transactions.*') && request('type') === 'out' ? 'active' : '' }}">
                        <i class="{{ $isTeknik ? 'fa-solid fa-dolly' : 'bi bi-box-arrow-in-up' }}"></i>
                        <span>Goods Issue</span>
                    </a>
                @else
                    <a href="{{ route('transactions.index') }}"
                        onclick="switchSection('transactionsSection', this); return false;"
                        data-section="transactionsSection"
                        class="sidebar-link {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Transaksi</span>
                    </a>
                @endif
            @endif

            {{-- Rekap Stok: All user --}}
            @if(!$isTeknik && ($canUseAdminMenus || auth()->user()->isManager() || auth()->user()->isStaff()))
                <a href="{{ route('stock.index') }}"
                    onclick="switchSection('stockSection', this); return false;"
                    data-section="stockSection"
                    class="sidebar-link {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data-fill"></i>
                    <span>Rekap Stok</span>
                </a>
            @endif

            {{-- Stuff Request: Admin & Staff --}}
            @if(!$isTeknik && ($canUseAdminMenus || auth()->user()->isStaff()))
                <a href="{{ route('stock-requests.index') }}"
                    onclick="switchSection('stockRequestsSection', this); return false;"
                    data-section="stockRequestsSection"
                    class="sidebar-link {{ request()->routeIs('stock-requests.*') ? 'active' : '' }}">
                    <i class="bi bi-cart-check-fill"></i>
                    <span>Stok Request</span>
                    @if(auth()->user()->isSuperAdmin() || (auth()->user()->isAdmin() && !$isTeknik) || $isTeknikManager)
                        @php $pendingStockReqCount = \App\Models\StockRequest::visibleFor(auth()->user())->pending()->count(); @endphp
                        @if($pendingStockReqCount > 0)
                            <span class="badge bg-warning text-dark">{{ $pendingStockReqCount }}</span>
                        @endif
                    @endif
                </a>

                <a href="{{ route('stuff-requests.index') }}"
                    onclick="switchSection('stuffRequestsSection', this); return false;"
                    data-section="stuffRequestsSection"
                    class="sidebar-link {{ request()->routeIs('stuff-requests.*') ? 'active' : '' }}">
                    <i class="bi bi-inbox-fill"></i>
                    <span>Permintaan Barang</span>
                    @php $pendingReqCount = \App\Models\StuffRequest::visibleFor(auth()->user())->pending()->count(); @endphp
                    @if($pendingReqCount > 0)
                        <span class="badge bg-warning text-dark">{{ $pendingReqCount }}</span>
                    @endif
                </a>
            @endif

            {{-- Laporan: Admin & Manager --}}
            @if(!$isTeknik && ($canUseAdminMenus || auth()->user()->isManager()))
                <div class="sidebar-label" style="margin-top: 8px;">Laporan</div>

                <a href="{{ route('reports.index') }}"
                    onclick="switchSection('reportsSection', this); return false;"
                    data-section="reportsSection"
                    class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Laporan</span>
                </a>
            @endif

            {{-- Pengaturan: Admin only --}}
            @if(!$isTeknik && $canUseAdminMenus)
                <div class="sidebar-label" style="margin-top: 8px;">Pengaturan</div>

                <a href="{{ route('users.index') }}"
                    onclick="switchSection('usersSection', this); return false;"
                    data-section="usersSection"
                    class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>{{ auth()->user()->isSuperAdmin() ? 'User Management Global' : 'Manajemen User' }}</span>
                </a>

                <a href="{{ route('import.index') }}"
                    onclick="switchSection('importSection', this); return false;"
                    data-section="importSection"
                    class="sidebar-link {{ request()->routeIs('import.*') ? 'active' : '' }}">
                    <i class="bi bi-cloud-arrow-up-fill"></i>
                    <span>Import Data</span>
                </a>
            @endif

            {{-- Approval User: Manager only --}}
            @if(auth()->user()->isManager() && !$isTeknikManager)
                <div class="sidebar-label" style="margin-top: 8px;">Pengaturan</div>

                <a href="{{ route('pendingUsers.index', ['account_status' => 'pending']) }}"
                    onclick="switchSection('usersSection', this); return false;"
                    data-section="usersSection"
                    class="sidebar-link {{ request()->routeIs('pendingUsers.*') ? 'active' : '' }}">
                    <i class="bi bi-person-check-fill"></i>
                    <span>Approval User</span>
                    @php
                        $pendingUsersCount = 0;
                        try {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'account_status')) {
                                $pendingUsersCount = \App\Models\User::visibleFor(auth()->user())->where('account_status', 'pending')->count();
                            }
                        } catch (\Throwable $e) {
                            $pendingUsersCount = 0;
                        }
                    @endphp
                    @if($pendingUsersCount > 0)
                        <span class="badge bg-warning text-dark">{{ $pendingUsersCount }}</span>
                    @endif
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-widgets">
                <!-- Dark/Light Mode Toggle -->
                <button class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti tema" id="themeToggle">
                    <i class="bi bi-sun-fill icon-sun"></i>
                    <i class="bi bi-moon-fill icon-moon"></i>
                </button>

                <div class="sidebar-clock-container">
                    <span class="fw-bold tanggal" style="font-size:12px;">
                        {{ now()->translatedFormat('l, d M Y') }}
                        <span id="live-clock" class="ms-1 fw-bold"></span>
                    </span>
                </div>
            </div>

            <div class="sidebar-user">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">{{ ucfirst(auth()->user()->role) }} @if(auth()->user()->bidang) - {{ ucfirst(auth()->user()->bidang) }} @endif</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0;" id="logoutForm">
                    @csrf
                    <button type="button" class="btn-icon"
                        onclick="swalConfirm('Logout', 'Apakah Anda yakin ingin keluar?', 'warning', 'Ya, Logout', '#logoutForm')"
                        style="background:rgba(255,255,255,0.08);border:none;color:rgba(255,255,255,0.5);width:34px;height:34px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;"
                        title="Logout">
                        <i class="bi bi-power"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content sidebar-collapsed">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-container">
                <button class="btn-sidebar-toggle" onclick="toggleSidebar()" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-main">
                    <div class="topbar-right">
                        <div class="topbar-right-actions" id="topbarRightActions"></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="page-content" id="sectionShell">
            <div class="topbar-titles">
                <div class="page-title" id="pageTitle">@yield('title', 'Dashboard')</div>
                <div class="page-subtitle" id="pageSubtitle">@yield('subtitle', '')</div>
            </div>

            <section id="{{ $currentSectionId }}" class="content-section active" data-loaded="true"
                data-title="@yield('title', 'Dashboard')"
                data-subtitle="@yield('subtitle', '')">
                @yield('content')
            </section>
        </div>
    </main>


        <footer class="main-footer sidebar-collapsed">
            <div class="footer-content">
                <img src="{{ asset('images/footer-ship.png') }}" alt="Logo Kapal" class="footer-ship-img" loading="lazy">
                <div class="copyright">
                    &copy; 2026 Port Management Unit Suralaya
                </div>
            </div>
        </footer>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        window.inventrackConfig = {
            currentSectionId: @json($currentSectionId),
            isTeknik: @json(auth()->check() && auth()->user()->isTeknik()),
            isSuperAdmin: @json(auth()->check() && auth()->user()->isSuperAdmin()),
            flash: {
                success: @json(session('success')),
                error: @json(session('error')),
            },
        };
    </script>
    @vite('resources/js/app.js')

    @stack('scripts')
</body>

</html>
@endif
