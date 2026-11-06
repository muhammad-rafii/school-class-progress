@extends('layouts.app')

@section('title', 'Rekap Presensi Siswa')
@section('page-title', 'Rekap Presensi Siswa')
@section('breadcrumb', 'Kesiswaan')
@section('meta_description', 'Panel Rekap Presensi Siswa untuk Waka Kurikulum, Guru BK, dan Wali Kelas berdasarkan izin resmi siswa yang telah disetujui.')

@php
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
    $fmtDateFull = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('l, d F Y') : '-';
    $isImage = fn ($p) => $p && preg_match('/\.(jpe?g|png|gif|webp)$/i', $p);
    $roleId = (int) session('id_role');
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));

    $selectedKelasNama = 'Semua Kelas';
    if (!empty($filterKelas)) {
        $found = $kelasList->firstWhere('id_kelas', $filterKelas);
        if ($found) {
            $selectedKelasNama = trim(($found->tingkat ?? '') . ' ' . ($found->id_jurusan ?? $found->nama_kelas ?? $found->id_kelas));
        }
    }
@endphp

@push('styles')
<style>
    /* Styling khusus Rekap Presensi */
    .stat-badge-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .stat-badge-chip.sakit { background: #fef3c7; color: #b45309; }
    .stat-badge-chip.izin  { background: #e0f2fe; color: #0369a1; }
    .stat-badge-chip.total { background: #f1f5f9; color: #334155; }
    .stat-badge-chip.days  { background: #ede9fe; color: #6d28d9; }

    /* Print styling */
    @media print {
        .app-sidebar, .app-topbar, .page-hero, .card-filter, .card-toolbar, 
        .table-actions, .btn, .modal, .sidebar-backdrop {
            display: none !important;
        }
        .app-main {
            margin: 0 !important;
            padding: 0 !important;
        }
        .card-modern {
            box-shadow: none !important;
            border: none !important;
        }
        .print-header {
            display: block !important;
            margin-bottom: 20px;
            text-align: center;
        }
        body {
            background: #fff !important;
            color: #000 !important;
        }
    }
    .print-header {
        display: none;
    }
</style>
@endpush

@section('content')
    {{-- Header Cetak Resmi (Hanya tampil saat Print) --}}
    <div class="print-header">
        <h3 class="mb-1 fw-bold text-uppercase">Laporan Rekapitulasi Presensi &amp; Perizinan Siswa</h3>
        <h5 class="mb-1 text-muted">SMK Negeri 1 Bantul</h5>
        <p class="mb-0 small">
            Kelas: <strong>{{ $selectedKelasNama }}</strong> | 
            Periode: <strong>{{ $filterStart ? $fmtDate($filterStart) : 'Awal' }} s/d {{ $filterEnd ? $fmtDate($filterEnd) : 'Sekarang' }}</strong> |
            Status: <strong>Disetujui oleh Guru BK atau Wali Kelas</strong>
        </p>
        <hr class="my-3">
    </div>

    {{-- ===== HERO ===== --}}
    <section class="page-hero mb-4">
        <i class="fa-solid fa-clipboard-user hero-illustration"></i>
        <span class="hero-eyebrow"><i class="fa-solid fa-sun"></i> {{ $greeting }}</span>
        <h2>Rekap Presensi Siswa</h2>
        <p>Rekapitulasi presensi siswa yang perizinannya (Sakit / Izin) telah <strong>disetujui oleh Guru BK maupun Wali Kelas</strong>.</p>
        <div class="hero-chips">
            @if(!empty($isWaliKelas))
                <span class="hero-chip"><i class="fa-solid fa-graduation-cap"></i> Kelas Binaan: <strong>{{ $waliKelasId ?: 'Belum diatur' }}</strong></span>
            @endif
            <span class="hero-chip"><i class="fa-solid fa-user-check"></i> {{ $totalSiswaIzin }} Siswa Berizin</span>
            <span class="hero-chip"><i class="fa-solid fa-circle-check"></i> {{ $totalApprovedLeaves }} Izin Disetujui Guru BK / Wali Kelas</span>
            <span class="hero-chip"><i class="fa-solid fa-triangle-exclamation"></i> {{ $totalPerhatian }} Perlu Perhatian</span>
            <span class="hero-chip"><i class="fa-solid fa-layer-group"></i> {{ $selectedKelasNama }}</span>
        </div>
    </section>

    @if(!empty($isWaliKelas) && empty($waliKelasId))
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4"></i>
            <div>
                <strong>Perhatian:</strong> Akun Anda berstatus <strong>Wali Kelas</strong> namun belum ditentukan kelas binaannya oleh Waka Kurikulum. Silakan hubungi Waka Kurikulum untuk mengatur kelas Anda.
            </div>
        </div>
    @endif

    {{-- ===== STAT CARDS ===== --}}
    <div class="row g-3 mb-4 animate-in">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-user-check"></i></div>
                <div>
                    <div class="stat-label">Siswa Izin / Sakit</div>
                    <div class="stat-value">{{ $totalSiswaIzin }}</div>
                    <div class="stat-meta">Disetujui Guru BK / Wali Kelas</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-emerald">
                <div class="icon-box solid lg"><i class="fa-solid fa-clipboard-check"></i></div>
                <div>
                    <div class="stat-label">Total Izin Disetujui</div>
                    <div class="stat-value">{{ $totalApprovedLeaves }}</div>
                    <div class="stat-meta">Akumulasi pengajuan disetujui</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-amber">
                <div class="icon-box solid lg"><i class="fa-solid fa-notes-medical"></i></div>
                <div>
                    <div class="stat-label">Sakit &amp; Izin Resmi</div>
                    <div class="stat-value">{{ $totalSakit + $totalIzin }}</div>
                    <div class="stat-meta">{{ $totalSakit }} Sakit • {{ $totalIzin }} Izin</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-rose">
                <div class="icon-box solid lg"><i class="fa-solid fa-calendar-xmark"></i></div>
                <div>
                    <div class="stat-label">Total Hari Tidak Masuk</div>
                    <div class="stat-value">{{ $totalHariAbsen }} Hari</div>
                    <div class="stat-meta">{{ $totalPerhatian }} siswa dengan izin &ge; 3x</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== FILTER CARD ===== --}}
    <div class="card-modern card-filter mb-4">
        <div class="card-modern-header">
            <div class="icon-box solid tone-accent"><i class="fa-solid fa-sliders"></i></div>
            <div>
                <h2>Filter &amp; Periode Rekap</h2>
                <p>Sesuaikan kelas atau rentang tanggal perizinan siswa yang ingin ditinjau</p>
            </div>
        </div>
        <div class="p-3 pt-0">
            <form action="{{ route('kesiswaan.presensi.rekap') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">Pilih Kelas</label>
                    @if(!empty($isWaliKelas))
                        <input type="text" class="form-control bg-light" value="{{ $selectedKelasNama }}" readonly>
                        <input type="hidden" name="kelas" value="{{ $waliKelasId }}">
                        <div class="form-text text-muted" style="font-size: 0.72rem;">Terkunci ke kelas binaan Anda.</div>
                    @else
                        <select name="kelas" class="form-select">
                            <option value="">-- Semua Kelas --</option>
                            @foreach($kelasList as $k)
                                <option value="{{ $k->id_kelas }}" {{ (string)$filterKelas === (string)$k->id_kelas ? 'selected' : '' }}>
                                    {{ trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas)) }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $filterStart }}">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold small text-muted">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $filterEnd }}">
                </div>
                <div class="col-md-2 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100" title="Terapkan Filter">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                    @if(!empty($filterKelas) || !empty($filterStart) || !empty($filterEnd))
                        <a href="{{ route('kesiswaan.presensi.rekap') }}" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="fa-solid fa-arrow-rotate-left"></i>
                        </a>
                    @endif
                    <button type="button" class="btn btn-outline-dark" onclick="window.print()" title="Cetak Rekap Presensi">
                        <i class="fa-solid fa-print"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= TABEL UTAMA: REKAP PRESENSI SISWA TERVERIFIKASI ================= --}}
    <div class="card-modern">
        <div class="card-modern-header">
            <div class="icon-box solid tone-accent"><i class="fa-solid fa-clipboard-user"></i></div>
            <div>
                <h2>Rekap Presensi Siswa Terverifikasi</h2>
                <p>Data kehadiran siswa yang perizinannya telah disetujui oleh Guru BK maupun Wali Kelas</p>
            </div>
        </div>

        <div class="card-toolbar justify-content-end">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" class="form-control" id="searchSiswa" placeholder="Cari nama siswa, NISN, kelas..." data-table-search="#tableRekapSiswa">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-modern" id="tableRekapSiswa">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Data Siswa</th>
                        <th>Kelas</th>
                        <th class="text-center" title="Sakit Disetujui">Sakit Disetujui</th>
                        <th class="text-center" title="Izin Disetujui">Izin Disetujui</th>
                        <th class="text-center" title="Total Pengajuan Disetujui">Total Izin Disetujui</th>
                        <th class="text-center" title="Total Hari Absen">Total Hari Izin</th>
                        <th>Status Kehadiran</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($siswaList as $idx => $s)
                        @php
                            $isPerluPerhatian = $s->total_pengajuan >= 3 || $s->total_hari_absen >= 5;
                            $filterTag = $isPerluPerhatian ? 'perlu-bk' : 'normal';
                            $kelasLabel = $s->kelas ? trim(($s->kelas->tingkat ?? '') . ' ' . ($s->kelas->id_jurusan ?? $s->kelas->nama_kelas ?? $s->kelas->id_kelas)) : ($s->id_kelas ?? '-');
                        @endphp
                        <tr data-row 
                            data-status="{{ $filterTag }}" 
                            data-has-sakit="{{ $s->total_sakit > 0 ? '1' : '0' }}"
                            data-has-izin="{{ $s->total_izin > 0 ? '1' : '0' }}"
                            class="{{ $isPerluPerhatian ? 'table-warning bg-opacity-25' : '' }}">
                            <td class="text-muted text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="mini-avatar {{ $isPerluPerhatian ? 'bg-danger text-white' : '' }}">
                                        {{ strtoupper(mb_substr($s->nama_siswa ?? 'S', 0, 1)) }}
                                    </span>
                                    <div>
                                        <div class="cell-title fw-bold text-dark">{{ $s->nama_siswa }}</div>
                                        <div class="cell-sub text-muted">NISN: {{ $s->nisn ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $kelasLabel }}</span>
                            </td>
                            <td class="text-center">
                                <span class="stat-badge-chip sakit">{{ $s->total_sakit }}</span>
                            </td>
                            <td class="text-center">
                                <span class="stat-badge-chip izin">{{ $s->total_izin }}</span>
                            </td>
                            <td class="text-center">
                                <span class="stat-badge-chip total fw-bold">
                                    {{ $s->total_pengajuan }} kali
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="stat-badge-chip days fw-bold">
                                    {{ $s->total_hari_absen }} Hari
                                </span>
                            </td>
                            <td>
                                @if($isPerluPerhatian)
                                    <span class="badge-soft danger dot" title="Pengajuan izin/sakit telah mencapai {{ $s->total_pengajuan }} kali">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Perlu Perhatian (&ge; 3x)
                                    </span>
                                @else
                                    <span class="badge-soft success dot">
                                        <i class="fa-solid fa-circle-check"></i> Izin Disetujui
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRiwayat-{{ $s->id_siswa }}">
                                    <i class="fa-solid fa-clipboard-list me-1"></i> Rincian Izin
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                                    <h4>Belum ada siswa dengan izin disetujui</h4>
                                    <p>Data rekap presensi hanya menampilkan siswa yang perizinannya telah disetujui oleh Guru BK maupun Wali Kelas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr class="js-no-result d-none">
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                <h4>Tidak ditemukan</h4>
                                <p>Tidak ada data siswa berizin yang cocok dengan filter atau pencarian Anda.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= MODAL RINCIAN IZIN PER SISWA ================= --}}
    @foreach($siswaList as $s)
        @php
            $kelasLabel = $s->kelas ? (($s->kelas->tingkat ?? '') . ' ' . ($s->kelas->nama_kelas ?? $s->kelas->id_kelas)) : ($s->id_kelas ?? '-');
            $riwayatIzin = $s->riwayat_izin ?? collect();
            $isPerluPerhatian = $s->total_pengajuan >= 3 || $s->total_hari_absen >= 5;
        @endphp
        <div class="modal fade" id="modalRiwayat-{{ $s->id_siswa }}" tabindex="-1" aria-labelledby="labelRiwayat-{{ $s->id_siswa }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="icon-box solid tone-accent"><i class="fa-solid fa-user-graduate"></i></div>
                            <div>
                                <h5 class="modal-title fw-bold" id="labelRiwayat-{{ $s->id_siswa }}">Catatan Perizinan: {{ $s->nama_siswa }}</h5>
                                <div class="small text-muted">NISN: {{ $s->nisn ?? '-' }} • Kelas: {{ $kelasLabel }}</div>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        {{-- Ringkasan Angka Siswa --}}
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-warning fw-bold fs-5">{{ $s->total_sakit }}</div>
                                    <div class="small text-muted">Sakit Disetujui</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-info fw-bold fs-5">{{ $s->total_izin }}</div>
                                    <div class="small text-muted">Izin Disetujui</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded text-center bg-light">
                                    <div class="text-primary fw-bold fs-5">{{ $s->total_hari_absen }} Hari</div>
                                    <div class="small text-muted">Total Hari Absen</div>
                                </div>
                            </div>
                        </div>

                        {{-- Alert Konseling jika Izin >= 3 --}}
                        @if($isPerluPerhatian)
                            <div class="alert alert-danger d-flex align-items-center gap-3 mb-3 py-2" role="alert">
                                <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                                <div>
                                    <strong>Perhatian Guru BK &amp; Wali Kelas!</strong>
                                    <div class="small">Siswa ini telah tercatat mengajukan izin/sakit sebanyak <strong>{{ $s->total_pengajuan }} kali (total {{ $s->total_hari_absen }} hari)</strong> yang telah disetujui. Direkomendasikan untuk evaluasi kondisi belajar dan pemantauan kesehatan siswa.</div>
                                </div>
                            </div>
                        @endif

                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-list-check me-1"></i> Riwayat Izin Disetujui Guru BK / Wali Kelas</h6>
                        
                        @if($riwayatIzin->isNotEmpty())
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Periode Izin</th>
                                            <th>Jenis</th>
                                            <th>Alasan / Keterangan</th>
                                            <th>Bukti Dokumen</th>
                                            <th>Disetujui Oleh</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($riwayatIzin as $ra)
                                            @php
                                                $isSkt = preg_match('/sakit/i', $ra->reason);
                                                $chip = $isSkt ? 'sakit' : 'izin';
                                                $stStart = \Carbon\Carbon::parse($ra->start_date);
                                                $stEnd = \Carbon\Carbon::parse($ra->end_date);
                                                $dur = max(1, $stStart->diffInDays($stEnd) + 1);
                                                $verName = $ra->verifier->nama_lengkap ?? ($ra->verifier->username ?? 'Wali Kelas / Guru BK');
                                                $verRole = match((int)($ra->verifier->id_role ?? 0)) {
                                                    3 => 'Wali Kelas',
                                                    7 => 'Guru BK',
                                                    4 => 'Waka Kurikulum',
                                                    default => 'Wali Kelas / Guru BK'
                                                };
                                            @endphp
                                            <tr>
                                                <td class="text-nowrap">
                                                    <div class="fw-semibold">{{ $fmtDate($ra->start_date) }} s/d {{ $fmtDate($ra->end_date) }}</div>
                                                    <div class="small text-muted">{{ $dur }} Hari</div>
                                                </td>
                                                <td>
                                                    <span class="stat-badge-chip {{ $chip }}">{{ $isSkt ? 'Sakit' : 'Izin' }}</span>
                                                </td>
                                                <td class="small">{{ $ra->reason ?: '-' }}</td>
                                                <td>
                                                    @if($isImage($ra->document_path))
                                                        <a href="{{ asset($ra->document_path) }}" target="_blank" class="thumb" title="Lihat surat">
                                                            <img src="{{ asset($ra->document_path) }}" alt="Surat izin" loading="lazy">
                                                        </a>
                                                    @elseif($ra->document_path)
                                                        <a href="{{ asset($ra->document_path) }}" target="_blank" class="file-chip">
                                                            <i class="fa-solid fa-file-pdf"></i> Dokumen
                                                        </a>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    <div class="fw-semibold"><i class="fa-solid fa-check text-success me-1"></i> {{ $verName }}</div>
                                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $verRole }}</div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3 text-center text-muted bg-light rounded border">
                                <i class="fa-solid fa-circle-check text-success fs-3 mb-1 d-block"></i>
                                <strong>Tidak Ada Riwayat Ketidakhadiran</strong>
                                <p class="small mb-0">Siswa ini tidak memiliki riwayat izin atau sakit yang diajukan.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Filter tabs (Semua Siswa Berizin / Perlu BK / Sakit / Izin)
        const filterTabs = document.querySelectorAll('.filter-tab[data-filter-target]');
        filterTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                const targetTableSelector = this.getAttribute('data-filter-target');
                const targetTable = document.querySelector(targetTableSelector);
                const filterValue = this.getAttribute('data-filter');

                // Active styling
                this.closest('.filter-tabs').querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                if (!targetTable) return;

                const rows = targetTable.querySelectorAll('tbody tr[data-row]');
                let visibleCount = 0;

                rows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    const hasSakit = row.getAttribute('data-has-sakit') === '1';
                    const hasIzin = row.getAttribute('data-has-izin') === '1';

                    let show = false;
                    if (filterValue === 'all') {
                        show = true;
                    } else if (filterValue === 'perlu-bk' && rowStatus === 'perlu-bk') {
                        show = true;
                    } else if (filterValue === 'sakit' && hasSakit) {
                        show = true;
                    } else if (filterValue === 'izin' && hasIzin) {
                        show = true;
                    }

                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                const noResultRow = targetTable.querySelector('.js-no-result');
                if (noResultRow) {
                    noResultRow.classList.toggle('d-none', visibleCount > 0);
                }
            });
        });

        // Search inputs
        const searchInputs = document.querySelectorAll('input[data-table-search]');
        searchInputs.forEach(input => {
            input.addEventListener('input', function () {
                const targetTableSelector = this.getAttribute('data-table-search');
                const targetTable = document.querySelector(targetTableSelector);
                const query = this.value.trim().toLowerCase();

                if (!targetTable) return;

                const rows = targetTable.querySelectorAll('tbody tr[data-row]');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    const match = text.includes(query);
                    row.style.display = match ? '' : 'none';
                    if (match) visibleCount++;
                });

                const noResultRow = targetTable.querySelector('.js-no-result');
                if (noResultRow) {
                    noResultRow.classList.toggle('d-none', visibleCount > 0);
                }
            });
        });
    });
</script>
@endpush
