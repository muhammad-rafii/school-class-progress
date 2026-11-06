@php
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Role & Menu Sidebar
    |--------------------------------------------------------------------------
    | 4 = Waka Kurikulum, 3 = Wali Kelas, 2 = Guru, 7 = Guru BK, 5 = Siswa
    */
    $navRoleId = (int) session('id_role');

    $navRoles = [
        4 => ['label' => 'Waka Kurikulum', 'theme' => 'waka', 'icon' => 'fa-user-tie'],
        3 => ['label' => 'Wali Kelas & Guru Pengampu', 'theme' => 'walas', 'icon' => 'fa-chalkboard-user'],
        2 => ['label' => 'Guru Pengampu', 'theme' => 'guru', 'icon' => 'fa-person-chalkboard'],
        7 => ['label' => 'Guru BK', 'theme' => 'bk', 'icon' => 'fa-hand-holding-heart'],
        5 => ['label' => 'Siswa', 'theme' => 'siswa', 'icon' => 'fa-user-graduate'],
    ];

    $menuGuru = [
        'Kegiatan Mengajar' => [
            ['label' => 'Jurnal Mengajar', 'route' => 'jurnal.index', 'icon' => 'fa-book-open-reader'],
            ['label' => 'Jadwal Pelajaran', 'route' => 'jadwal.index', 'icon' => 'fa-calendar-days'],
        ],
    ];

    $menuKesiswaan = [
        ['label' => 'Verifikasi Izin Siswa', 'route' => 'kesiswaan.izin.index', 'icon' => 'fa-clipboard-check'],
        ['label' => 'Rekap Presensi Siswa', 'route' => 'kesiswaan.presensi.rekap', 'icon' => 'fa-clipboard-user'],
    ];

    $navMenus = [
        4 => [
            'Akademik' => [
                ['label' => 'Jadwal Pelajaran', 'route' => 'jadwal.index', 'icon' => 'fa-calendar-days'],
                ['label' => 'Rekap Jurnal Guru', 'route' => 'waka.jurnal.rekap', 'icon' => 'fa-book-bookmark'],
                ['label' => 'Rekap Presensi Siswa', 'route' => 'kesiswaan.presensi.rekap', 'icon' => 'fa-clipboard-user'],
            ],
            'Administrasi' => [
                ['label' => 'Manajemen Akun', 'route' => 'akun.index', 'icon' => 'fa-users-gear'],
            ],
        ],
        3 => [
            'Kegiatan Mengajar' => [
                ['label' => 'Jurnal Mengajar', 'route' => 'jurnal.index', 'icon' => 'fa-book-open-reader'],
                ['label' => 'Jadwal Pelajaran', 'route' => 'jadwal.index', 'icon' => 'fa-calendar-days'],
            ],
            'Kesiswaan & Perizinan' => $menuKesiswaan,
        ],
        2 => $menuGuru,
        7 => [
            'Kesiswaan' => $menuKesiswaan,
        ],
        5 => [
            'Layanan Siswa' => [
                ['label' => 'Pengajuan Izin / Sakit', 'route' => 'siswa.izin.index', 'icon' => 'fa-envelope-open-text'],
            ],
        ],
    ];

    // Rute Dashboard utama per role
    $navDashboardRoute = match($navRoleId) {
        4 => 'waka.dashboard',
        2 => 'guru.dashboard',
        3, 7 => 'kesiswaan.izin.index',
        5, 1 => 'siswa.izin.index',
        default => null,
    };

    $navRole = $navRoles[$navRoleId] ?? ['label' => 'Pengguna', 'theme' => 'default', 'icon' => 'fa-user'];
    $navMenu = $navMenus[$navRoleId] ?? [];
    $navName = session('nama_lengkap') ?: (session('username') ?: 'Pengguna');
    $navParts = preg_split('/\s+/', trim($navName));
    $navInitials = strtoupper(mb_substr($navParts[0] ?? 'U', 0, 1) . mb_substr($navParts[1] ?? '', 0, 1));
    $navFoto = session('foto_profil');
@endphp
<!DOCTYPE html>
<html lang="id" class="no-transition">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="@yield('meta_description', 'School Class Progress - Sistem manajemen jadwal pelajaran, jurnal mengajar, dan perizinan siswa.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SCP</title>

    <script>
        // Terapkan status sidebar sebelum render agar tidak berkedip
        if (localStorage.getItem('scp-sidebar-collapsed') === '1') {
            document.documentElement.classList.add('sb-collapsed');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    @stack('styles')
</head>

<body class="app-body theme-{{ $navRole['theme'] }}">

    @include('layouts.partials.sidebar')
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-main">
        {{-- ================= TOPBAR ================= --}}
        <header class="app-topbar">
            <button type="button" class="topbar-btn" id="sidebarToggle" aria-label="Buka/tutup sidebar">
                <i class="fa-solid fa-bars-staggered"></i>
            </button>

            <div class="topbar-title">
                <div class="crumbs">
                    <span>SCP</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span>@yield('breadcrumb', $navRole['label'])</span>
                </div>
                <h1>@yield('page-title', 'Dashboard')</h1>
            </div>

            <div class="topbar-right">
                <div class="topbar-date d-none d-md-flex">
                    <i class="fa-regular fa-calendar"></i>
                    {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}
                </div>

                <div class="dropdown">
                    <button class="user-chip" type="button" id="userMenuButton" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <span class="avatar">@if($navFoto)<img src="{{ asset($navFoto) }}" alt="Foto profil" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">@else{{ $navInitials }}@endif</span>
                        <span class="chip-text text-start">
                            <span class="chip-name d-block">{{ $navName }}</span>
                            <span class="chip-role d-block">{{ $navRole['label'] }}</span>
                        </span>
                        <i class="fa-solid fa-chevron-down small text-muted chip-text"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-modern" aria-labelledby="userMenuButton">
                        <li class="px-3 py-2">
                            <div class="fw-bold small">{{ $navName }}</div>
                            <div class="text-muted" style="font-size:.75rem">{{ session('username') }} ·
                                {{ $navRole['label'] }}
                            </div>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a href="{{ route('profile.index') }}" class="dropdown-item">
                                <i class="fa-solid fa-user-gear me-2 text-primary"></i> Profil Saya
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <button type="submit" form="logoutForm" class="dropdown-item text-danger"
                                onclick="return confirm('Yakin ingin keluar?')">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        {{-- ================= CONTENT ================= --}}
        <main class="app-content">
            @if(session('success'))
                <div class="alert alert-modern success alert-dismissible fade show mb-4" role="alert">
                    <span class="alert-icon"><i class="fa-solid fa-check"></i></span>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-modern danger alert-dismissible fade show mb-4" role="alert">
                    <span class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-modern danger alert-dismissible fade show mb-4" role="alert">
                    <span class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="app-footer">
            <span>&copy; {{ date('Y') }} School Class Progress</span>
            <span>Masuk sebagai <strong>{{ $navRole['label'] }}</strong></span>
        </footer>
    </div>

    <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const html = document.documentElement;
            const isDesktop = () => window.matchMedia('(min-width: 992px)').matches;

            // Aktifkan kembali transisi setelah halaman siap
            requestAnimationFrame(() => requestAnimationFrame(() => html.classList.remove('no-transition')));

            // Toggle sidebar: collapse di desktop, off-canvas di mobile
            document.getElementById('sidebarToggle')?.addEventListener('click', () => {
                if (isDesktop()) {
                    const collapsed = html.classList.toggle('sb-collapsed');
                    localStorage.setItem('scp-sidebar-collapsed', collapsed ? '1' : '0');
                } else {
                    html.classList.toggle('sb-open');
                }
            });

            const closeMobile = () => html.classList.remove('sb-open');
            document.getElementById('sidebarClose')?.addEventListener('click', closeMobile);
            document.getElementById('sidebarBackdrop')?.addEventListener('click', closeMobile);
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMobile(); });
            window.addEventListener('resize', () => { if (isDesktop()) closeMobile(); });

            // Tooltip saat sidebar diciutkan
            const tip = document.createElement('div');
            tip.className = 'sb-tooltip';
            document.body.appendChild(tip);

            document.querySelectorAll('.app-sidebar [data-tooltip]').forEach((el) => {
                el.addEventListener('mouseenter', () => {
                    if (!isDesktop() || !html.classList.contains('sb-collapsed')) return;
                    const r = el.getBoundingClientRect();
                    tip.textContent = el.dataset.tooltip;
                    tip.style.top = (r.top + r.height / 2) + 'px';
                    tip.style.left = (r.right + 14) + 'px';
                    tip.classList.add('show');
                });
                el.addEventListener('mouseleave', () => tip.classList.remove('show'));
            });

            // Tutup otomatis notifikasi sukses setelah 6 detik
            document.querySelectorAll('.alert-modern.success').forEach((el) => {
                setTimeout(() => bootstrap.Alert.getOrCreateInstance(el).close(), 6000);
            });

            // ===== Helper tabel: pencarian + filter status =====
            // <input data-table-search="#tabel">, <button data-filter-target="#tabel" data-filter="pending">
            const applyTableFilter = (table) => {
                const q = (table.dataset.query || '').toLowerCase();
                const f = table.dataset.filter || 'all';
                let visible = 0;
                table.querySelectorAll('tbody tr[data-row]').forEach((tr) => {
                    const okText = !q || tr.textContent.toLowerCase().includes(q);
                    const okFilter = f === 'all' || tr.dataset.status === f;
                    const show = okText && okFilter;
                    tr.classList.toggle('d-none', !show);
                    if (show) visible++;
                });
                table.querySelector('tbody tr.js-no-result')?.classList.toggle('d-none', visible > 0);
            };

            document.querySelectorAll('[data-table-search]').forEach((input) => {
                const table = document.querySelector(input.dataset.tableSearch);
                if (!table) return;
                input.addEventListener('input', () => { table.dataset.query = input.value.trim(); applyTableFilter(table); });
            });

            document.querySelectorAll('[data-filter-target]').forEach((btn) => {
                const table = document.querySelector(btn.dataset.filterTarget);
                if (!table) return;
                btn.addEventListener('click', () => {
                    btn.parentElement.querySelectorAll('[data-filter-target]').forEach((b) => b.classList.remove('active'));
                    btn.classList.add('active');
                    table.dataset.filter = btn.dataset.filter;
                    applyTableFilter(table);
                });
            });

            // ===== Preview gambar sebelum upload =====
            // <input type="file" data-preview="#previewBox">
            document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
                const box = document.querySelector(input.dataset.preview);
                if (!box) return;
                input.addEventListener('change', () => {
                    const file = input.files?.[0];
                    if (file && file.type.startsWith('image/')) {
                        box.innerHTML = '<img alt="Pratinjau">';
                        box.querySelector('img').src = URL.createObjectURL(file);
                        box.classList.add('show');
                    } else {
                        box.innerHTML = '';
                        box.classList.remove('show');
                    }
                });
            });
        })();
    </script>
    @if($navRoleId === 2)
        {{-- ===== Pengingat: belum isi jurnal (15 menit sebelum jam pelajaran selesai) ===== --}}
        <div class="toast-container position-fixed top-0 end-0 p-3" id="jurnalReminderBox" style="z-index:2000"></div>
        <script>
            (function () {
                const url = @json(route('jurnal.pengingat'));
                const jurnalUrl = @json(route('jurnal.index'));
                const box = document.getElementById('jurnalReminderBox');
                const dismissed = new Set();
                const POLL_MS = 30000;

                const store = {
                    get: (k) => { try { return localStorage.getItem(k); } catch (e) { return null; } },
                    set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) { } }
                };
                const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

                const render = (list) => {
                    // Hapus toast yang sudah tidak relevan (jurnal terisi / jam habis)
                    box.querySelectorAll('[data-key]').forEach((el) => {
                        if (!list.some((r) => r.key === el.dataset.key)) el.remove();
                    });

                    list.forEach((r) => {
                        const text = 'Belum isi jurnal — ' + r.mapel + ' (' + r.kelas + ')';

                        // Notifikasi browser, sekali per jadwal per hari
                        const nKey = 'scp-jurnal-notif-' + r.key;
                        if ('Notification' in window && Notification.permission === 'granted' && !store.get(nKey)) {
                            store.set(nKey, '1');
                            try { new Notification('Belum isi jurnal', { body: text + '. Jam pelajaran selesai ' + r.selesai + '.' }); } catch (e) { }
                        }

                        if (dismissed.has(r.key)) return;

                        let el = box.querySelector('[data-key="' + r.key + '"]');
                        if (!el) {
                            el = document.createElement('div');
                            el.className = 'toast show align-items-start border-0 text-bg-warning mb-2';
                            el.setAttribute('role', 'alert');
                            el.dataset.key = r.key;
                            box.appendChild(el);
                        }
                        el.innerHTML =
                            '<div class="d-flex"><div class="toast-body">' +
                            '<div class="fw-bold"><i class="fa-solid fa-bell me-1"></i> Belum isi jurnal!</div>' +
                            '<div>' + esc(r.mapel) + ' · ' + esc(r.kelas) + '</div>' +
                            '<div class="small">Jam pelajaran selesai ' + esc(r.selesai) + ' (sisa ±' + esc(r.sisa_menit) + ' menit).</div>' +
                            '<div class="mt-2 d-flex gap-2 flex-wrap">' +
                            '<a href="' + jurnalUrl + '" class="btn btn-sm btn-dark">Isi Jurnal</a>' +
                            ('Notification' in window && Notification.permission === 'default'
                                ? '<button type="button" class="btn btn-sm btn-outline-dark" data-enable-notif>Aktifkan notifikasi browser</button>' : '') +
                            '</div></div>' +
                            '<button type="button" class="btn-close me-2 m-auto" aria-label="Tutup" data-dismiss></button></div>';

                        el.querySelector('[data-dismiss]').addEventListener('click', () => { dismissed.add(r.key); el.remove(); });
                        el.querySelector('[data-enable-notif]')?.addEventListener('click', (e) => {
                            Notification.requestPermission().then(() => e.target.remove());
                        });
                    });
                };

                const check = () => {
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                        .then((res) => res.ok ? res.json() : { reminders: [] })
                        .then((data) => render(data.reminders || []))
                        .catch(() => { });
                };

                check();
                setInterval(check, POLL_MS);
                document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
            })();
        </script>
    @endif
    @stack('scripts')
</body>

</html>