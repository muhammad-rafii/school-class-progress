{{-- Sidebar dinamis berdasarkan role (variabel disiapkan di layouts/app.blade.php) --}}
<aside class="app-sidebar" id="appSidebar" aria-label="Navigasi utama">
    <div class="sidebar-brand">
        <div class="brand-logo brand-logo-img">
            <img src="{{ asset('logo-smk.png.png') }}" alt="Logo SMK Negeri 1 Bantul" class="sidebar-school-logo">
        </div>
        <div class="brand-text">
            <span class="brand-name">SCP</span>
            <span class="brand-sub">School Class Progress</span>
        </div>
        <button type="button" class="sidebar-close d-lg-none" id="sidebarClose" aria-label="Tutup sidebar">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Link Dashboard utama di sidebar untuk semua user --}}
    @if(!empty($navDashboardRoute))
        @php $isDashActive = request()->routeIs($navDashboardRoute); @endphp
        <div class="px-3 pt-2 pb-1">
            <a href="{{ route($navDashboardRoute) }}"
               id="nav-dashboard-main"
               class="nav-link-item {{ $isDashActive ? 'active' : '' }}"
               data-tooltip="Dashboard"
               @if($isDashActive) aria-current="page" @endif>
                <span class="nav-icon"><i class="fa-solid fa-house"></i></span>
                <span class="nav-text">Dashboard</span>
                <i class="fa-solid fa-chevron-right nav-chevron"></i>
            </a>
        </div>
    @endif

    <nav class="sidebar-nav">
        @foreach($navMenu as $section => $items)
            <div class="nav-section-label">{{ $section }}</div>
            <ul>
                @foreach($items as $item)
                    @php $isActive = request()->routeIs($item['route']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           id="nav-{{ str_replace('.', '-', $item['route']) }}"
                           class="nav-link-item {{ $isActive ? 'active' : '' }}"
                           data-tooltip="{{ $item['label'] }}"
                           @if($isActive) aria-current="page" @endif>
                            <span class="nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                            <span class="nav-text">{{ $item['label'] }}</span>
                            <i class="fa-solid fa-chevron-right nav-chevron"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <button type="submit" form="logoutForm" id="nav-logout" class="nav-link-item logout"
                data-tooltip="Keluar" onclick="return confirm('Yakin ingin keluar?')">
            <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
            <span class="nav-text">Keluar</span>
        </button>
    </div>
</aside>
