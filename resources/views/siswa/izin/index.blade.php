@extends('layouts.app')

@section('title', 'Pengajuan Izin / Sakit')
@section('page-title', 'Pengajuan Izin / Sakit')
@section('breadcrumb', 'Layanan Siswa')
@section('meta_description', 'Portal siswa untuk mengajukan izin atau sakit beserta dokumen pendukung dan memantau status verifikasinya.')

@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
    $isImage = fn($p) => $p && preg_match('/\.(jpe?g|png|gif|webp)$/i', $p);

    $statusMap = [
        'Approved' => ['key' => 'approved', 'label' => 'Disetujui', 'class' => 'success'],
        'Rejected' => ['key' => 'rejected', 'label' => 'Ditolak', 'class' => 'danger'],
        'Pending' => ['key' => 'pending', 'label' => 'Menunggu', 'class' => 'warning'],
    ];
    $statusOf = fn($s) => $statusMap[$s] ?? $statusMap['Pending'];

    $riwayatSorted = $riwayatIzin->sortByDesc('start_date')->values();
    $countApproved = $riwayatIzin->where('status_approval', 'Approved')->count();
    $countRejected = $riwayatIzin->where('status_approval', 'Rejected')->count();
    $countPending = $riwayatIzin->count() - $countApproved - $countRejected;

    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="page-hero mb-4">
        <i class="fa-solid fa-user-graduate hero-illustration"></i>
        <span class="hero-eyebrow"><i class="fa-solid fa-sun"></i> {{ $greeting }}</span>
        <h2>Halo, {{ session('nama_lengkap') ?: session('username') }}</h2>
        <p>Tidak bisa hadir ke sekolah? Ajukan izin atau sakit di sini dan pantau status verifikasinya oleh Wali Kelas /
            Guru BK.</p>
        <div class="hero-chips">
            <span class="hero-chip"><i class="fa-solid fa-id-card"></i> NISN {{ session('username') }}</span>
            <span class="hero-chip"><i class="fa-solid fa-hourglass-half"></i> {{ $countPending }} menunggu
                verifikasi</span>
        </div>
    </section>

    {{-- ===== STATUS ===== --}}
    <div class="row g-3 mb-4 animate-in">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-envelope-open-text"></i></div>
                <div>
                    <div class="stat-label">Total Pengajuan</div>
                    <div class="stat-value">{{ $riwayatIzin->count() }}</div>
                    <div class="stat-meta">Semua riwayat</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-amber">
                <div class="icon-box solid lg"><i class="fa-solid fa-hourglass-half"></i></div>
                <div>
                    <div class="stat-label">Menunggu</div>
                    <div class="stat-value">{{ $countPending }}</div>
                    <div class="stat-meta">Sedang ditinjau</div>
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

    <div class="row g-4">
        {{-- ===== Form Pengajuan ===== --}}
        <div class="col-lg-5 col-xxl-4">
            <div class="card-modern sticky-lg">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-accent"><i class="fa-solid fa-paper-plane"></i></div>
                    <div>
                        <h2>Form Surat Izin</h2>
                        <p>Lengkapi data &amp; lampirkan bukti</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    <form action="{{ route('siswa.izin.store') }}" method="POST" enctype="multipart/form-data"
                        id="izinForm">
                        @csrf
                        <div class="form-section-title">Periode</div>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label" for="start_date">Tanggal Mulai</label>
                                <input type="date" name="start_date" id="start_date" class="form-control"
                                    min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" required>
                                <div class="form-text">Tidak bisa memilih tanggal yang sudah lewat.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="end_date">Tanggal Selesai</label>
                                <input type="date" name="end_date" id="end_date" class="form-control"
                                    min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" required>
                            </div>
                        </div>

                        <div class="form-section-title">Keterangan</div>
                        <div class="mb-3">
                            <label class="form-label" for="reason">Alasan</label>
                            <textarea name="reason" id="reason" class="form-control" rows="3" required
                                placeholder="Contoh: Sakit demam / Izin acara keluarga">{{ old('reason') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="document">Bukti (Surat Dokter / Foto)</label>
                            <input type="file" name="document" id="document" class="form-control" accept=".pdf,image/*"
                                required data-preview="#docPreview">
                            <div class="form-text">Format PDF, JPG, JPEG, PNG · Maksimal 2 MB.</div>
                            <div class="upload-preview" id="docPreview"></div>
                        </div>

                        <button type="submit" id="btnKirimIzin" class="btn btn-accent w-100">
                            <i class="fa-solid fa-paper-plane me-1"></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Riwayat ===== --}}
        <div class="col-lg-7 col-xxl-8">
            <div class="card-modern">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-slate"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div>
                        <h2>Riwayat Pengajuan Saya</h2>
                        <p>{{ $riwayatIzin->count() }} pengajuan tercatat</p>
                    </div>
                </div>

                <div class="card-toolbar">
                    <div class="filter-tabs" role="tablist" aria-label="Filter status">
                        <button type="button" class="filter-tab active" data-filter-target="#tableRiwayat" data-filter="all"
                            id="filter-all">Semua <span class="count">{{ $riwayatIzin->count() }}</span></button>
                        <button type="button" class="filter-tab" data-filter-target="#tableRiwayat" data-filter="pending"
                            id="filter-pending">Menunggu <span class="count">{{ $countPending }}</span></button>
                        <button type="button" class="filter-tab" data-filter-target="#tableRiwayat" data-filter="approved"
                            id="filter-approved">Disetujui <span class="count">{{ $countApproved }}</span></button>
                        <button type="button" class="filter-tab" data-filter-target="#tableRiwayat" data-filter="rejected"
                            id="filter-rejected">Ditolak <span class="count">{{ $countRejected }}</span></button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-modern" id="tableRiwayat">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Alasan</th>
                                <th>Dokumen</th>
                                <th>Status</th>
                                <th>Alasan Penolakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riwayatSorted as $izin)
                                @php $st = $statusOf($izin->status_approval); @endphp
                                <tr data-row data-status="{{ $st['key'] }}">
                                    <td>
                                        <div class="date-range text-nowrap">
                                            <span class="fw-semibold">{{ $fmtDate($izin->start_date) }}</span>
                                            <span class="to">s/d</span>
                                            <span class="fw-semibold">{{ $fmtDate($izin->end_date) }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-clamp">{{ $izin->reason }}</div>
                                    </td>
                                    <td>
                                        @if($isImage($izin->document_path))
                                            <a href="{{ asset($izin->document_path) }}" target="_blank" class="thumb"
                                                title="Lihat dokumen">
                                                <img src="{{ asset($izin->document_path) }}" alt="Dokumen izin" loading="lazy">
                                            </a>
                                        @elseif($izin->document_path)
                                            <a href="{{ asset($izin->document_path) }}" target="_blank" class="file-chip"><i
                                                    class="fa-solid fa-file-pdf"></i> Lihat</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><span class="badge-soft {{ $st['class'] }} dot">{{ $st['label'] }}</span></td>
                                    <td>
                                        @if($st['key'] === 'rejected')
                                            <div class="text-danger" style="min-width:200px; max-width:280px; white-space:normal;">
                                                <i class="fa-solid fa-comment-dots me-1"></i>
                                                {{ !empty($izin->catatan_verifikasi) ? $izin->catatan_verifikasi : 'Wali Kelas/BK tidak menuliskan alasan.' }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-regular fa-envelope"></i></div>
                                            <h4>Belum ada riwayat</h4>
                                            <p>Pengajuan izin yang Anda kirim akan tampil di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            @if($riwayatIzin->isNotEmpty())
                                <tr class="js-no-result d-none">
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-solid fa-filter"></i></div>
                                            <h4>Tidak ada data</h4>
                                            <p>Tidak ada pengajuan dengan status ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mulai = document.getElementById('start_date');
        const selesai = document.getElementById('end_date');
        if (!mulai || !selesai) return;

        const hariIni = mulai.min;

        function sinkron() {
            // Tanggal selesai tidak boleh sebelum tanggal mulai (dan tidak sebelum hari ini)
            const batas = (mulai.value && mulai.value > hariIni) ? mulai.value : hariIni;
            selesai.min = batas;
            if (selesai.value && selesai.value < batas) selesai.value = batas;
        }

        mulai.addEventListener('change', sinkron);
        sinkron();
    });
</script>
@endpush
