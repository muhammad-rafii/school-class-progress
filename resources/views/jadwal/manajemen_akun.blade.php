@extends('layouts.app')

@section('title', 'Manajemen Akun')
@section('page-title', 'Manajemen Akun')
@section('breadcrumb', 'Administrasi')
@section('meta_description', 'Buat akun pengguna baru dan atur hak akses (role) untuk Waka, Guru Pengampu, Wali Kelas, Guru BK, dan Siswa.')

@php
    $roleMap = [
        4 => ['label' => 'Waka Kurikulum', 'class' => 'violet',  'icon' => 'fa-user-tie'],
        3 => ['label' => 'Wali Kelas',     'class' => 'success', 'icon' => 'fa-chalkboard-user'],
        2 => ['label' => 'Guru Pengampu',  'class' => 'info',    'icon' => 'fa-person-chalkboard'],
        7 => ['label' => 'Guru BK',        'class' => 'accent',  'icon' => 'fa-hand-holding-heart'],
        5 => ['label' => 'Siswa',          'class' => 'warning', 'icon' => 'fa-user-graduate'],
        1 => ['label' => 'Siswa',          'class' => 'warning', 'icon' => 'fa-user-graduate'],
    ];
    $roleOf = fn ($id) => $roleMap[(int) $id] ?? ['label' => 'Role ' . $id, 'class' => 'neutral', 'icon' => 'fa-user'];

    $countWaka  = $users->where('id_role', 4)->count();
    $countGuru  = $users->whereIn('id_role', [2, 3, 7])->count();
    $countSiswa = $users->whereIn('id_role', [1, 5])->count();
    $oldRole    = (string) old('id_role', '');
@endphp

@section('content')
    {{-- ===== STATUS ===== --}}
    <div class="row g-3 mb-4 animate-in">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="stat-label">Total Akun</div>
                    <div class="stat-value">{{ $users->count() }}</div>
                    <div class="stat-meta">Terdaftar di sistem</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-violet">
                <div class="icon-box solid lg"><i class="fa-solid fa-user-tie"></i></div>
                <div>
                    <div class="stat-label">Waka / Admin</div>
                    <div class="stat-value">{{ $countWaka }}</div>
                    <div class="stat-meta">Akses penuh sistem</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-emerald">
                <div class="icon-box solid lg"><i class="fa-solid fa-person-chalkboard"></i></div>
                <div>
                    <div class="stat-label">Guru &amp; BK</div>
                    <div class="stat-value">{{ $countGuru }}</div>
                    <div class="stat-meta">Pengampu, Walas &amp; BK</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-amber">
                <div class="icon-box solid lg"><i class="fa-solid fa-user-graduate"></i></div>
                <div>
                    <div class="stat-label">Siswa</div>
                    <div class="stat-value">{{ $countSiswa }}</div>
                    <div class="stat-meta">Akun siswa terdaftar</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== Form Buat Akun ===== --}}
        <div class="col-lg-5 col-xxl-4">
            <div class="card-modern sticky-lg">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-accent"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <h2>Buat Akun Baru</h2>
                        <p>Isi data &amp; tentukan hak akses</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    <form action="{{ route('akun.store') }}" method="POST" id="akunForm">
                        @csrf
                        <div class="form-section-title">Data Akun</div>

                        <div class="mb-3">
                            <label class="form-label" for="username">Username / NIP / NISN</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-at"></i>
                                <input type="text" name="username" id="username" class="form-control" value="{{ old('username') }}" required placeholder="Masukkan username / NIP / NISN">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="nama_lengkap">Nama Lengkap</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-user"></i>
                                <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required placeholder="Contoh: Budi Santoso, S.Pd.">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="no_hp">Nomor HP / WhatsApp <span class="text-muted small">(Opsional)</span></label>
                            <div class="input-icon">
                                <i class="fa-solid fa-phone"></i>
                                <input type="tel" name="no_hp" id="no_hp" class="form-control" value="{{ old('no_hp') }}" placeholder="Contoh: 081234567890">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email (Gmail) <span class="text-muted small">(Opsional, untuk OTP lupa password)</span></label>
                            <div class="input-icon">
                                <i class="fa-solid fa-envelope"></i>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="contoh: nama@gmail.com">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="password" id="password" class="form-control" required minlength="5" placeholder="Minimal 5 karakter">
                            </div>
                        </div>

                        <div class="form-section-title">Hak Akses</div>
                        <div class="option-grid mb-2" style="grid-template-columns: 1fr;">
                            <label class="option-card tone-violet">
                                <input type="radio" name="id_role" value="4" {{ $oldRole === '4' ? 'checked' : '' }} required>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-user-tie"></i></span>
                                    <span>
                                        <span class="option-title d-block">Waka / Admin</span>
                                        <span class="option-desc d-block">Kelola jadwal, akun &amp; konfigurasi</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                            <label class="option-card tone-blue">
                                <input type="radio" name="id_role" value="2" {{ $oldRole === '2' ? 'checked' : '' }}>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-person-chalkboard"></i></span>
                                    <span>
                                        <span class="option-title d-block">Guru Pengampu</span>
                                        <span class="option-desc d-block">KBM, jadwal &amp; isi jurnal mengajar</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                            <label class="option-card tone-emerald">
                                <input type="radio" name="id_role" value="3" {{ $oldRole === '3' ? 'checked' : '' }}>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-chalkboard-user"></i></span>
                                    <span>
                                        <span class="option-title d-block">Wali Kelas &amp; Guru Pengampu</span>
                                        <span class="option-desc d-block">2 Peran: Guru KBM (jadwal &amp; jurnal) + Wali Kelas binaan</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                            <label class="option-card tone-accent">
                                <input type="radio" name="id_role" value="7" {{ $oldRole === '7' ? 'checked' : '' }}>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-hand-holding-heart"></i></span>
                                    <span>
                                        <span class="option-title d-block">Guru BK</span>
                                        <span class="option-desc d-block">Hanya verifikasi &amp; approve izin siswa</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                            <label class="option-card tone-amber">
                                <input type="radio" name="id_role" value="5" {{ in_array($oldRole, ['1', '5']) ? 'checked' : '' }}>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-user-graduate"></i></span>
                                    <span>
                                        <span class="option-title d-block">Siswa</span>
                                        <span class="option-desc d-block">Otomatis terdaftar di database siswa</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                        </div>

                        <div id="kelasSection" class="mb-3 {{ in_array($oldRole, ['1', '5']) ? '' : 'd-none' }}">
                            <label class="form-label" for="id_kelas">Kelas Siswa <span class="text-muted small">(Opsional)</span></label>
                            <select name="id_kelas" id="id_kelas" class="form-select">
                                <option value="">-- Pilih Kelas (Opsional) --</option>
                                @if(isset($kelas))
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                            {{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas)) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div id="kelasWaliSection" class="mb-3 {{ $oldRole === '3' ? '' : 'd-none' }}">
                            <label class="form-label fw-semibold text-primary" for="id_kelas_wali">
                                <i class="fa-solid fa-graduation-cap me-1"></i> Kelas Binaan Wali Kelas
                            </label>
                            <select name="id_kelas_wali" id="id_kelas_wali" class="form-select">
                                <option value="">-- Pilih Kelas Binaan --</option>
                                @if(isset($kelas))
                                    @foreach($kelas as $k)
                                        <option value="{{ $k->id_kelas }}" {{ old('id_kelas_wali') == $k->id_kelas ? 'selected' : '' }}>
                                            {{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas)) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text text-muted" style="font-size: 0.72rem;">Wali Kelas akan otomatis memiliki akses mengajar sebagai Guru sekaligus memverifikasi izin kelas ini.</div>
                        </div>

                        <div class="form-text mb-4">Gunakan NIP untuk username guru atau NISN untuk username siswa.</div>

                        <button type="submit" id="btnSaveAkun" class="btn btn-accent w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Akun Baru
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Daftar Akun ===== --}}
        <div class="col-lg-7 col-xxl-8">
            <div class="card-modern">
                <div class="card-modern-header flex-wrap">
                    <div class="icon-box solid tone-slate"><i class="fa-solid fa-users-gear"></i></div>
                    <div>
                        <h2>Daftar Akun Terdaftar</h2>
                        <p>{{ $users->count() }} akun</p>
                    </div>
                    <div class="header-actions search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" class="form-control" id="searchAkun" placeholder="Cari nama, username, role...">
                    </div>
                </div>

                <style>
                    .role-tabs-scroll { overflow-x: auto; overflow-y: hidden; scrollbar-width: thin; }
                    .role-tabs-scroll .nav-item { flex: 0 0 auto; }
                    .role-tabs-scroll .nav-link { padding-left: .6rem !important; padding-right: .6rem !important; font-size: .85rem; }
                    .akun-pagination .page-link { cursor: pointer; }
                </style>
                {{-- Filter Tabs Sesuai Role --}}
                <div class="px-3 pt-2 pb-0 border-bottom bg-light">
                    <ul class="nav nav-tabs border-0 flex-nowrap role-tabs-scroll" id="roleFilterTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-2 px-3 fw-semibold text-sm role-filter-btn text-nowrap" data-filter="all">
                                Semua Akun <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $users->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-3 fw-semibold text-sm role-filter-btn text-nowrap" data-filter="waka">
                                <i class="fa-solid fa-user-tie text-purple me-1"></i> Waka Kurikulum <span class="badge bg-violet-subtle text-violet ms-1">{{ $countWaka }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-3 fw-semibold text-sm role-filter-btn text-nowrap" data-filter="guru">
                                <i class="fa-solid fa-person-chalkboard text-info me-1"></i> Guru Pengampu <span class="badge bg-info-subtle text-info ms-1">{{ $users->where('id_role', 2)->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-3 fw-semibold text-sm role-filter-btn text-nowrap" data-filter="wali">
                                <i class="fa-solid fa-chalkboard-user text-success me-1"></i> Wali Kelas <span class="badge bg-success-subtle text-success ms-1">{{ $users->where('id_role', 3)->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-3 fw-semibold text-sm role-filter-btn text-nowrap" data-filter="bk">
                                <i class="fa-solid fa-hand-holding-heart text-danger me-1"></i> Guru BK <span class="badge bg-danger-subtle text-danger ms-1">{{ $users->where('id_role', 7)->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-2 px-3 fw-semibold text-sm role-filter-btn text-nowrap" data-filter="siswa">
                                <i class="fa-solid fa-user-graduate text-warning me-1"></i> Siswa <span class="badge bg-warning-subtle text-warning-emphasis ms-1">{{ $countSiswa }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="table-responsive">
                    <table class="table table-modern" id="tableAkun">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pengguna</th>
                                <th>Role</th>
                                <th>Kelas / Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $index => $u)
                                @php
                                    $r = $roleOf($u->id_role);
                                    $uName = ($u->nama_lengkap ?? null) ?: $u->username;
                                    $isSiswa = in_array((int)$u->id_role, [1, 5]);
                                    $sObj = $isSiswa && isset($siswaMap) ? ($siswaMap->get($u->username) ?? null) : null;
                                    
                                    // Tentukan role category filter
                                    $roleCategory = match((int)$u->id_role) {
                                        4 => 'waka',
                                        2 => 'guru',
                                        3 => 'wali',
                                        7 => 'bk',
                                        1, 5 => 'siswa',
                                        default => 'other',
                                    };
                                @endphp
                                <tr data-row data-role-group="{{ $roleCategory }}">
                                    <td><span class="row-index">{{ $index + 1 }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="mini-avatar">{{ strtoupper(mb_substr($uName, 0, 1)) }}</span>
                                            <div>
                                                <div class="cell-title">{{ $uName }}</div>
                                                <div class="cell-sub"><i class="fa-solid fa-at me-1"></i>{{ $u->username }}</div>
                                                @if(!empty($u->email))
                                                    <div class="cell-sub text-muted mt-0" style="font-size: 0.72rem;">
                                                        <i class="fa-regular fa-envelope me-1 text-primary"></i>{{ $u->email }}
                                                    </div>
                                                @endif
                                                @if(!empty($u->no_hp))
                                                    <div class="cell-sub text-muted mt-0" style="font-size: 0.72rem;">
                                                        <i class="fa-solid fa-phone me-1 text-success"></i>{{ $u->no_hp }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-soft {{ $r['class'] }}"><i class="fa-solid {{ $r['icon'] }}"></i> {{ $r['label'] }}</span>
                                        @if((int)$u->id_role === 3)
                                            <div class="mt-1">
                                                @if(!empty($u->id_kelas))
                                                    <span class="badge bg-success-subtle text-success-emphasis border px-2 py-0" style="font-size: 0.72rem;">
                                                        <i class="fa-solid fa-chalkboard-user me-1"></i> Kelas: {{ $u->id_kelas }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger-emphasis border px-2 py-0" style="font-size: 0.72rem;">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Kelas belum diatur
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            @if($isSiswa)
                                                @if($sObj && !empty($sObj->id_kelas))
                                                    <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                                                        <i class="fa-solid fa-graduation-cap me-1"></i> {{ $sObj->id_kelas }}
                                                    </span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalAturKelas" data-id-siswa="{{ $sObj->id_siswa ?? '' }}" data-nama="{{ $sObj->nama_siswa ?? $uName }}" data-nisn="{{ $u->username }}" data-kelas="{{ $sObj->id_kelas ?? '' }}">
                                                        Ubah Kelas
                                                    </button>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1">
                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Belum ada kelas
                                                    </span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalAturKelas" data-id-siswa="{{ $sObj->id_siswa ?? '' }}" data-nama="{{ $sObj->nama_siswa ?? $uName }}" data-nisn="{{ $u->username }}" data-kelas="{{ $sObj->id_kelas ?? '' }}">
                                                        Atur Kelas
                                                    </button>
                                                @endif
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalUbahRole" data-id-user="{{ $u->id_user }}" data-nama="{{ $uName }}" data-username="{{ $u->username }}" data-nohp="{{ $u->no_hp }}" data-role="{{ in_array((int)$u->id_role, [1, 5]) ? 5 : (int)$u->id_role }}" data-kelas="{{ $u->id_kelas ?? '' }}" title="Ubah hak akses pengguna ini">
                                                    <i class="fa-solid fa-user-gear me-1"></i> Ubah Role
                                                </button>
                                            @endif

                                            @if((int)$u->id_user !== (int)session('id_user'))
                                                <form action="{{ route('akun.destroy', $u->id_user) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $u->username }} ({{ $uName }})?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.72rem;" title="Hapus Akun">
                                                        <i class="fa-solid fa-trash-can"></i> Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-solid fa-user-slash"></i></div>
                                            <h4>Belum ada akun</h4>
                                            <p>Buat akun pertama melalui form di samping.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            @if($users->isNotEmpty())
                                <tr class="js-no-result d-none">
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                            <h4>Tidak ditemukan</h4>
                                            <p>Tidak ada akun yang cocok dengan pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi: 25 akun per halaman --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-3 border-top akun-pagination" id="akunPagination">
                    <div class="text-muted small" id="akunPageInfo"></div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="akunPrev"><i class="fa-solid fa-chevron-left me-1"></i> Sebelumnya</button>
                        <span class="small fw-semibold px-2" id="akunPageNum"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="akunNext">Selanjutnya <i class="fa-solid fa-chevron-right ms-1"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== MODAL BERSAMA: ATUR KELAS SISWA (satu modal untuk semua siswa, diisi via JS) ===== --}}
    <div class="modal fade" id="modalAturKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <form action="{{ route('akun.updateKelas') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_siswa" id="mk_id_siswa">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold">
                            <i class="fa-solid fa-graduation-cap text-primary me-2"></i>
                            Atur Kelas Siswa
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Siswa</label>
                            <input type="text" class="form-control bg-light" id="mk_nama" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">NISN / Username</label>
                            <input type="text" class="form-control bg-light" id="mk_nisn" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="mk_kelas">Pilih Kelas Siswa</label>
                            <select name="id_kelas" id="mk_kelas" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->id_kelas }}">{{ $k->nama_kelas ?? $k->id_kelas }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Siswa akan otomatis masuk ke daftar presensi ketika guru mengajar di kelas ini.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== MODAL BERSAMA: UBAH ROLE PENGGUNA (satu modal untuk semua akun, diisi via JS) ===== --}}
    <div class="modal fade" id="modalUbahRole" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <form action="{{ route('akun.updateRole') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_user" id="mr_id_user">
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold">
                            <i class="fa-solid fa-user-gear text-primary me-2"></i>
                            Ubah Hak Akses (Role)
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Nama Pengguna</label>
                            <input type="text" class="form-control bg-light" id="mr_nama" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Username / NIP</label>
                            <input type="text" class="form-control bg-light" id="mr_username" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="mr_nohp">Nomor HP / WhatsApp <span class="text-muted small fw-normal">(Opsional)</span></label>
                            <div class="input-icon">
                                <i class="fa-solid fa-phone"></i>
                                <input type="tel" name="no_hp" id="mr_nohp" class="form-control" placeholder="Contoh: 081234567890">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="mr_role">Pilih Hak Akses Baru</label>
                            <select name="id_role" id="mr_role" class="form-select select-role-change" data-target="#mr_wali_box" required>
                                <option value="4">Waka Kurikulum / Admin</option>
                                <option value="2">Guru Pengampu (KBM &amp; Jurnal)</option>
                                <option value="3">Wali Kelas (Guru KBM + Verifikasi Izin Kelas)</option>
                                <option value="7">Guru BK (Hanya Verifikasi Izin)</option>
                                <option value="5">Siswa</option>
                            </select>
                            <div class="form-text">Wali Kelas memiliki fungsi penuh sebagai Guru (jadwal &amp; jurnal KBM) sekaligus memverifikasi izin siswa kelas binaannya.</div>
                        </div>
                        <div class="mb-3 d-none" id="mr_wali_box">
                            <label class="form-label fw-semibold text-primary" for="mr_kelas">
                                <i class="fa-solid fa-graduation-cap me-1"></i> Tentukan Kelas Binaan Wali Kelas
                            </label>
                            <select name="id_kelas" id="mr_kelas" class="form-select">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($kelas as $k)
                                    <option value="{{ $k->id_kelas }}">{{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas)) }}</option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted">Wali Kelas hanya dapat melihat dan menyetujui (approve) izin siswa dari kelas yang dipilih di sini.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roleRadios = document.querySelectorAll('input[name="id_role"]');
            const kelasSection = document.getElementById('kelasSection');
            const kelasWaliSection = document.getElementById('kelasWaliSection');

            roleRadios.forEach(radio => {
                radio.addEventListener('change', function () {
                    if (this.value === '5' || this.value === '1') {
                        if (kelasSection) kelasSection.classList.remove('d-none');
                        if (kelasWaliSection) kelasWaliSection.classList.add('d-none');
                    } else if (this.value === '3') {
                        if (kelasSection) kelasSection.classList.add('d-none');
                        if (kelasWaliSection) kelasWaliSection.classList.remove('d-none');
                    } else {
                        if (kelasSection) kelasSection.classList.add('d-none');
                        if (kelasWaliSection) kelasWaliSection.classList.add('d-none');
                    }
                });
            });

            // Toggle dropdown kelas binaan saat ubah role menjadi Wali Kelas di modal
            const roleSelects = document.querySelectorAll('.select-role-change');
            roleSelects.forEach(select => {
                select.addEventListener('change', function () {
                    const targetBox = document.querySelector(this.getAttribute('data-target'));
                    if (targetBox) {
                        if (this.value === '3') {
                            targetBox.classList.remove('d-none');
                        } else {
                            targetBox.classList.add('d-none');
                        }
                    }
                });
            });

            // Isi modal bersama saat dibuka (hemat memori: hanya 2 modal untuk ribuan akun)
            const modalKelas = document.getElementById('modalAturKelas');
            if (modalKelas) {
                modalKelas.addEventListener('show.bs.modal', function (e) {
                    const b = e.relatedTarget; if (!b) return;
                    document.getElementById('mk_id_siswa').value = b.dataset.idSiswa || '';
                    document.getElementById('mk_nama').value = b.dataset.nama || '';
                    document.getElementById('mk_nisn').value = b.dataset.nisn || '';
                    document.getElementById('mk_kelas').value = b.dataset.kelas || '';
                });
            }
            const modalRole = document.getElementById('modalUbahRole');
            if (modalRole) {
                modalRole.addEventListener('show.bs.modal', function (e) {
                    const b = e.relatedTarget; if (!b) return;
                    document.getElementById('mr_id_user').value = b.dataset.idUser || '';
                    document.getElementById('mr_nama').value = b.dataset.nama || '';
                    document.getElementById('mr_username').value = b.dataset.username || '';
                    document.getElementById('mr_nohp').value = b.dataset.nohp || '';
                    document.getElementById('mr_role').value = b.dataset.role || '';
                    document.getElementById('mr_kelas').value = b.dataset.kelas || '';
                    document.getElementById('mr_wali_box').classList.toggle('d-none', b.dataset.role !== '3');
                });
            }

            // Filter Tabs Berdasarkan Role
            const roleFilterBtns = document.querySelectorAll('.role-filter-btn');
            const tableRows = document.querySelectorAll('#tableAkun tbody tr[data-role-group]');
            let currentRoleFilter = 'all';

            roleFilterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    roleFilterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentRoleFilter = this.getAttribute('data-filter');
                    applyFilters();
                });
            });

            const searchAkun = document.getElementById('searchAkun');
            if (searchAkun) {
                searchAkun.addEventListener('input', function () {
                    applyFilters();
                });
            }

            const PER_PAGE = 25;
            let currentPage = 1;
            const pageInfo = document.getElementById('akunPageInfo');
            const pageNum = document.getElementById('akunPageNum');
            const btnPrev = document.getElementById('akunPrev');
            const btnNext = document.getElementById('akunNext');
            const pagination = document.getElementById('akunPagination');
            let filteredRows = [];

            function applyFilters(resetPage = true) {
                const keyword = searchAkun ? searchAkun.value.toLowerCase().trim() : '';
                if (resetPage) currentPage = 1;

                filteredRows = Array.from(tableRows).filter(row => {
                    const rowRole = row.getAttribute('data-role-group');
                    const matchRole = (currentRoleFilter === 'all') || (rowRole === currentRoleFilter);
                    const matchSearch = !keyword || row.innerText.toLowerCase().includes(keyword);
                    return matchRole && matchSearch;
                });

                renderPage();
            }

            function renderPage() {
                const total = filteredRows.length;
                const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
                if (currentPage > totalPages) currentPage = totalPages;
                const start = (currentPage - 1) * PER_PAGE;
                const end = start + PER_PAGE;

                tableRows.forEach(row => { row.style.display = 'none'; });
                filteredRows.forEach((row, i) => {
                    if (i >= start && i < end) {
                        row.style.display = '';
                        const idx = row.querySelector('.row-index');
                        if (idx) idx.textContent = i + 1; // nomor urut mengikuti hasil filter
                    }
                });

                const noResult = document.querySelector('.js-no-result');
                if (noResult) noResult.classList.toggle('d-none', total > 0);

                if (pagination) pagination.classList.toggle('d-none', total === 0);
                if (pageInfo) {
                    pageInfo.textContent = total === 0
                        ? ''
                        : 'Menampilkan ' + (start + 1) + '–' + Math.min(end, total) + ' dari ' + total + ' akun';
                }
                if (pageNum) pageNum.textContent = 'Halaman ' + currentPage + ' / ' + totalPages;
                if (btnPrev) btnPrev.disabled = currentPage <= 1;
                if (btnNext) btnNext.disabled = currentPage >= totalPages;
            }

            if (btnPrev) btnPrev.addEventListener('click', () => {
                if (currentPage > 1) { currentPage--; renderPage(); document.getElementById('tableAkun').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
            });
            if (btnNext) btnNext.addEventListener('click', () => {
                currentPage++; renderPage(); document.getElementById('tableAkun').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });

            applyFilters();
        });
    </script>
@endsection