@extends('layouts.app')

@section('title', 'Verifikasi Izin Siswa')
@section('page-title', 'Verifikasi Izin Siswa')
@section('breadcrumb', 'Kesiswaan')
@section('meta_description', 'Panel Wali Kelas & Guru BK untuk meninjau dan memverifikasi pengajuan izin / sakit siswa.')

@php
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
    $isImage = fn ($p) => $p && preg_match('/\.(jpe?g|png|gif|webp)$/i', $p);

    $statusMap = [
        'Approved' => ['key' => 'approved', 'label' => 'Disetujui', 'class' => 'success'],
        'Rejected' => ['key' => 'rejected', 'label' => 'Ditolak',   'class' => 'danger'],
        'Pending'  => ['key' => 'pending',  'label' => 'Menunggu',  'class' => 'warning'],
    ];
    $statusOf = fn ($s) => $statusMap[$s] ?? $statusMap['Pending'];

    $countApproved = $pendingLeaves->where('status_approval', 'Approved')->count();
    $countRejected = $pendingLeaves->where('status_approval', 'Rejected')->count();
    $countPending  = $pendingLeaves->count() - $countApproved - $countRejected;

    $roleId = (int) session('id_role');
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="page-hero mb-4">
        <i class="fa-solid {{ $roleId === 7 ? 'fa-hand-holding-heart' : 'fa-chalkboard-user' }} hero-illustration"></i>
        <span class="hero-eyebrow"><i class="fa-solid fa-sun"></i> {{ $greeting }}</span>
        <h2>Halo, {{ session('nama_lengkap') ?: session('username') }}</h2>
        <p>Tinjau pengajuan izin / sakit dari siswa, periksa dokumen pendukung, lalu setujui atau tolak dengan satu klik.</p>
        <div class="hero-chips">
            @if($roleId === 3)
                <span class="hero-chip"><i class="fa-solid fa-graduation-cap"></i> Kelas Bimbingan: <strong>{{ $waliKelasInfo ?: 'Belum diatur Waka' }}</strong></span>
            @endif
            <span class="hero-chip"><i class="fa-solid fa-hourglass-half"></i> {{ $countPending }} menunggu verifikasi</span>
            <span class="hero-chip"><i class="fa-solid fa-inbox"></i> {{ $pendingLeaves->count() }} total pengajuan</span>
        </div>
    </section>

    @if($roleId === 3 && empty($waliKelasInfo))
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-4"></i>
            <div>
                <strong>Perhatian:</strong> Akun Anda terdaftar sebagai <strong>Wali Kelas</strong> tetapi belum ditentukan kelas binaannya oleh Waka Kurikulum. Silakan hubungi Waka Kurikulum untuk mengatur kelas Anda melalui menu <em>Manajemen Akun</em>.
            </div>
        </div>
    @endif

    {{-- ===== STATUS ===== --}}
    <div class="row g-3 mb-4 animate-in">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-inbox"></i></div>
                <div>
                    <div class="stat-label">Total Pengajuan</div>
                    <div class="stat-value">{{ $pendingLeaves->count() }}</div>
                    <div class="stat-meta">Semua status</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-amber">
                <div class="icon-box solid lg"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-label">Menunggu</div>
                    <div class="stat-value">{{ $countPending }}</div>
                    <div class="stat-meta">Perlu ditinjau</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-emerald">
                <div class="icon-box solid lg"><i class="fa-solid fa-circle-check"></i></div>
                <div>
                    <div class="stat-label">Disetujui</div>
                    <div class="stat-value">{{ $countApproved }}</div>
                    <div class="stat-meta">Izin diterima</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-rose">
                <div class="icon-box solid lg"><i class="fa-solid fa-circle-xmark"></i></div>
                <div>
                    <div class="stat-label">Ditolak</div>
                    <div class="stat-value">{{ $countRejected }}</div>
                    <div class="stat-meta">Izin tidak diterima</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TABEL ===== --}}
    <div class="card-modern">
        <div class="card-modern-header">
            <div class="icon-box solid tone-accent"><i class="fa-solid fa-clipboard-check"></i></div>
            <div>
                <h2>Daftar Pengajuan Izin Siswa</h2>
                <p>Klik dokumen untuk melihat bukti sebelum memverifikasi</p>
            </div>
        </div>

        <div class="card-toolbar">
            <div class="filter-tabs" role="tablist" aria-label="Filter status">
                <button type="button" class="filter-tab active" data-filter-target="#tableIzin" data-filter="all" id="filter-all">Semua <span class="count">{{ $pendingLeaves->count() }}</span></button>
                <button type="button" class="filter-tab" data-filter-target="#tableIzin" data-filter="pending" id="filter-pending">Menunggu <span class="count">{{ $countPending }}</span></button>
                <button type="button" class="filter-tab" data-filter-target="#tableIzin" data-filter="approved" id="filter-approved">Disetujui <span class="count">{{ $countApproved }}</span></button>
                <button type="button" class="filter-tab" data-filter-target="#tableIzin" data-filter="rejected" id="filter-rejected">Ditolak <span class="count">{{ $countRejected }}</span></button>
            </div>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" class="form-control" id="searchIzin" placeholder="Cari nama, NISN, alasan..." data-table-search="#tableIzin">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-modern" id="tableIzin">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Tanggal</th>
                        <th>Alasan</th>
                        <th>Dokumen</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingLeaves as $item)
                        @php
                            $st = $statusOf($item->status_approval);
                            $sName = $item->student->nama_siswa ?? 'Siswa Tidak Ditemukan';
                        @endphp
                        <tr data-row data-status="{{ $st['key'] }}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="mini-avatar">{{ strtoupper(mb_substr($sName, 0, 1)) }}</span>
                                    <div>
                                        <div class="cell-title">{{ $sName }}</div>
                                        <div class="cell-sub">NISN: {{ $item->student->nisn ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="date-range text-nowrap">
                                    <span class="fw-semibold">{{ $fmtDate($item->start_date) }}</span>
                                    <span class="to">s/d</span>
                                    <span class="fw-semibold">{{ $fmtDate($item->end_date) }}</span>
                                </div>
                            </td>
                            <td><div class="text-clamp">{{ $item->reason }}</div></td>
                            <td>
                                @if($isImage($item->document_path))
                                    <a href="{{ asset($item->document_path) }}" target="_blank" class="thumb" title="Lihat dokumen">
                                        <img src="{{ asset($item->document_path) }}" alt="Dokumen izin" loading="lazy">
                                    </a>
                                @elseif($item->document_path)
                                    <a href="{{ asset($item->document_path) }}" target="_blank" class="file-chip"><i class="fa-solid fa-file-pdf"></i> Lihat</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge-soft {{ $st['class'] }} dot">{{ $st['label'] }}</span>
                                @if($st['key'] === 'rejected' && !empty($item->catatan_verifikasi))
                                    <div class="cell-sub mt-1 text-danger" style="max-width:220px; white-space:normal;">
                                        <i class="fa-solid fa-comment-dots me-1"></i>Catatan: {{ $item->catatan_verifikasi }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="table-actions">
                                    <form action="{{ route('kesiswaan.izin.update', $item->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="status_approval" value="Approved">
                                        <button type="submit" class="btn btn-sm btn-soft-success" id="approve-{{ $item->id }}"
                                                @disabled($st['key'] === 'approved')
                                                onclick="return confirm('Setujui pengajuan izin {{ addslashes($sName) }}?')">
                                            <i class="fa-solid fa-check"></i> Setujui
                                        </button>
                                    </form>
                                    {{-- Tombol Tolak membuka modal untuk mengisi catatan penolakan --}}
                                    <button type="button" class="btn btn-sm btn-soft-danger btn-tolak" id="reject-{{ $item->id }}"
                                            @disabled($st['key'] === 'rejected')
                                            data-action="{{ route('kesiswaan.izin.update', $item->id) }}"
                                            data-nama="{{ $sName }}"
                                            data-catatan="{{ $item->catatan_verifikasi }}">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-regular fa-envelope-open"></i></div>
                                    <h4>Belum ada pengajuan</h4>
                                    <p>Tidak ada pengajuan izin masuk dari siswa.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    @if($pendingLeaves->isNotEmpty())
                        <tr class="js-no-result d-none">
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                    <h4>Tidak ditemukan</h4>
                                    <p>Tidak ada pengajuan yang cocok dengan filter / pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== MODAL TOLAK IZIN (catatan wajib) ===== --}}
    <div class="modal fade" id="modalTolakIzin" tabindex="-1" aria-labelledby="modalTolakIzinLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="formTolakIzin" class="modal-content">
                @csrf
                <input type="hidden" name="status_approval" value="Rejected">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTolakIzinLabel">
                        <i class="fa-solid fa-circle-xmark text-danger me-1"></i> Tolak Pengajuan Izin
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Anda akan menolak izin atas nama <strong id="tolakNamaSiswa">-</strong>.</p>
                    <label for="catatan_verifikasi" class="form-label">Catatan penolakan <span class="text-danger">*</span></label>
                    <textarea name="catatan_verifikasi" id="catatan_verifikasi" class="form-control" rows="4" maxlength="500"
                              placeholder="Tuliskan alasan izin ditolak, misalnya: dokumen tidak jelas / surat dokter tidak dilampirkan..." required></textarea>
                    <div class="form-text">Catatan ini akan terlihat oleh siswa. Maksimal 500 karakter.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger" id="btnKonfirmasiTolak">
                        <i class="fa-solid fa-xmark me-1"></i> Tolak Izin
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalTolakIzin');
        if (!modalEl) return;
        const modal   = bootstrap.Modal.getOrCreateInstance(modalEl);
        const form    = document.getElementById('formTolakIzin');
        const nama    = document.getElementById('tolakNamaSiswa');
        const catatan = document.getElementById('catatan_verifikasi');

        document.querySelectorAll('.btn-tolak').forEach(function (btn) {
            btn.addEventListener('click', function () {
                form.action       = btn.dataset.action;
                nama.textContent  = btn.dataset.nama;
                catatan.value     = btn.dataset.catatan || '';
                modal.show();
            });
        });

        modalEl.addEventListener('shown.bs.modal', function () { catatan.focus(); });
    });
</script>
@endpush