@extends('layouts.app')

@section('title', 'Rekap Jurnal Guru')
@section('page-title', 'Rekap Jurnal Guru')
@section('breadcrumb', 'Akademik')
@section('meta_description', 'Pantau seluruh jurnal mengajar guru dan pelaksanaan KBM secara terpusat.')

@php
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
    $isImage = fn ($p) => $p && preg_match('/\.(jpe?g|png|gif|webp)$/i', $p);
    $jurnalHariIni = $jurnalList->filter(fn ($j) => $j->date && \Carbon\Carbon::parse($j->date)->isToday())->count();
    $guruUnik = $jurnalList->pluck('jadwal.guru.nama_guru')->filter()->unique()->count();
@endphp

@push('styles')
<style>
    .presensi-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.18rem 0.5rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .presensi-chip.hadir { background: #dcfce7; color: #15803d; }
    .presensi-chip.sakit { background: #fef3c7; color: #b45309; }
    .presensi-chip.izin  { background: #e0f2fe; color: #0369a1; }
    .presensi-chip.alfa  { background: #fee2e2; color: #b91c1c; }
</style>
@endpush

@section('content')
    {{-- ===== STATUS ===== --}}
    <div class="row g-3 mb-4 animate-in">
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-book-bookmark"></i></div>
                <div>
                    <div class="stat-label">Total Jurnal Guru</div>
                    <div class="stat-value">{{ $jurnalList->count() }}</div>
                    <div class="stat-meta">Semua kegiatan KBM</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card tone-emerald">
                <div class="icon-box solid lg"><i class="fa-solid fa-calendar-check"></i></div>
                <div>
                    <div class="stat-label">Jurnal Hari Ini</div>
                    <div class="stat-value">{{ $jurnalHariIni }}</div>
                    <div class="stat-meta">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-xl-4">
            <div class="stat-card tone-violet">
                <div class="icon-box solid lg"><i class="fa-solid fa-chalkboard-user"></i></div>
                <div>
                    <div class="stat-label">Guru Aktif Mengisi</div>
                    <div class="stat-value">{{ $guruUnik }}</div>
                    <div class="stat-meta">Guru pengampu KBM</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TABEL ===== --}}
    <div class="card-modern">
        <div class="card-modern-header flex-wrap">
            <div class="icon-box solid tone-accent"><i class="fa-solid fa-table-list"></i></div>
            <div>
                <h2>Rekap Seluruh Jurnal Mengajar</h2>
                <p>Diurutkan dari tanggal terbaru beserta dokumentasi KBM guru</p>
            </div>
            <div class="header-actions search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" class="form-control" id="searchRekap" placeholder="Cari guru, kelas, mapel, topik..." data-table-search="#tableRekap">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-modern" id="tableRekap">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Guru Pengampu</th>
                        <th>Kelas &amp; Mapel</th>
                        <th>Topik / Materi KBM</th>
                        <th class="text-center">Presensi Siswa</th>
                        <th class="text-center">Foto Bukti KBM</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jurnalList as $index => $jurnal)
                        @php
                            $gName = $jurnal->jadwal->guru->nama_guru ?? 'Guru Tidak Ditemukan';
                            $kelasLabel = $jurnal->jadwal && $jurnal->jadwal->kelas 
                                ? trim(($jurnal->jadwal->kelas->tingkat ?? '') . ' ' . ($jurnal->jadwal->kelas->id_jurusan ?? $jurnal->jadwal->kelas->nama_kelas ?? $jurnal->jadwal->kelas->id_kelas)) 
                                : ($jurnal->jadwal->id_kelas ?? '-');
                            $presensiDetails = $jurnal->presensi ? $jurnal->presensi->details : collect();
                            $cntHadir = $presensiDetails->where('status_kehadiran', 'Hadir')->count();
                            $cntSakit = $presensiDetails->where('status_kehadiran', 'Sakit')->count();
                            $cntIzin  = $presensiDetails->where('status_kehadiran', 'Izin')->count();
                            $cntAlfa  = $presensiDetails->where('status_kehadiran', 'Alfa')->count();
                            $totalPresensi = $presensiDetails->count();
                        @endphp
                        <tr data-row>
                            <td><span class="row-index">{{ $index + 1 }}</span></td>
                            <td><div class="cell-title text-nowrap">{{ $fmtDate($jurnal->date) }}</div></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="mini-avatar">{{ strtoupper(mb_substr($gName, 0, 1)) }}</span>
                                    <div class="cell-title">{{ $gName }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="cell-title">{{ $kelasLabel }}</div>
                                <div class="cell-sub">{{ $jurnal->jadwal->mataPelajaran->nama_mapel ?? '-' }}</div>
                            </td>
                            <td><div class="text-clamp">{{ $jurnal->topic_description }}</div></td>
                            <td class="text-center">
                                @if($totalPresensi > 0)
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                            @if($cntHadir > 0)
                                                <span class="presensi-chip hadir" title="{{ $cntHadir }} Hadir">H: {{ $cntHadir }}</span>
                                            @endif
                                            @if($cntSakit > 0)
                                                <span class="presensi-chip sakit" title="{{ $cntSakit }} Sakit">S: {{ $cntSakit }}</span>
                                            @endif
                                            @if($cntIzin > 0)
                                                <span class="presensi-chip izin" title="{{ $cntIzin }} Izin">I: {{ $cntIzin }}</span>
                                            @endif
                                            @if($cntAlfa > 0)
                                                <span class="presensi-chip alfa" title="{{ $cntAlfa }} Alfa">A: {{ $cntAlfa }}</span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 mt-1" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#modalPresensi-{{ $jurnal->id }}">
                                            <i class="fa-solid fa-users me-1"></i> Detail Presensi
                                        </button>
                                    </div>
                                @else
                                    <span class="badge-soft neutral">Belum diisi</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($isImage($jurnal->photo_path))
                                    <a href="{{ asset($jurnal->photo_path) }}" target="_blank" class="thumb" title="Lihat foto">
                                        <img src="{{ asset($jurnal->photo_path) }}" alt="Foto KBM" loading="lazy">
                                    </a>
                                @elseif($jurnal->photo_path)
                                    <a href="{{ asset($jurnal->photo_path) }}" target="_blank" class="file-chip"><i class="fa-solid fa-file"></i> Lihat</a>
                                @else
                                    <span class="badge-soft neutral">Tidak ada</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-regular fa-file-lines"></i></div>
                                    <h4>Belum ada jurnal</h4>
                                    <p>Belum ada guru yang mengisi jurnal mengajar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    @if($jurnalList->isNotEmpty())
                        <tr class="js-no-result d-none">
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                    <h4>Tidak ditemukan</h4>
                                    <p>Tidak ada jurnal yang cocok dengan pencarian Anda.</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== MODAL DETAIL PRESENSI UNTUK WAKA ===== --}}
    @foreach($jurnalList as $jurnal)
        @php
            $presensiDetails = $jurnal->presensi ? $jurnal->presensi->details : collect();
            $kelasLabel = $jurnal->jadwal && $jurnal->jadwal->kelas 
                ? trim(($jurnal->jadwal->kelas->tingkat ?? '') . ' ' . ($jurnal->jadwal->kelas->id_jurusan ?? $jurnal->jadwal->kelas->nama_kelas ?? $jurnal->jadwal->kelas->id_kelas)) 
                : ($jurnal->jadwal->id_kelas ?? '-');
            $mapelLabel = $jurnal->jadwal->mataPelajaran->nama_mapel ?? '-';
            $guruLabel = $jurnal->jadwal->guru->nama_guru ?? '-';
        @endphp
        @if($presensiDetails->isNotEmpty())
            <div class="modal fade" id="modalPresensi-{{ $jurnal->id }}" tabindex="-1" aria-labelledby="modalPresensiLabel-{{ $jurnal->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content shadow border-0">
                        <div class="modal-header bg-light">
                            <div>
                                <h6 class="modal-title fw-bold" id="modalPresensiLabel-{{ $jurnal->id }}">
                                    <i class="fa-solid fa-clipboard-user text-primary me-2"></i> Presensi KBM: {{ $kelasLabel }}
                                </h6>
                                <p class="small text-muted mb-0">
                                    {{ $fmtDate($jurnal->date) }} &bull; {{ $guruLabel }} &bull; {{ $mapelLabel }}
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-3">
                            <div class="row g-2 mb-3">
                                <div class="col-3">
                                    <div class="p-2 rounded text-center" style="background:#dcfce7; color:#15803d;">
                                        <div class="small fw-semibold">Hadir</div>
                                        <div class="fs-5 fw-bold">{{ $presensiDetails->where('status_kehadiran', 'Hadir')->count() }}</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 rounded text-center" style="background:#fef3c7; color:#b45309;">
                                        <div class="small fw-semibold">Sakit</div>
                                        <div class="fs-5 fw-bold">{{ $presensiDetails->where('status_kehadiran', 'Sakit')->count() }}</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 rounded text-center" style="background:#e0f2fe; color:#0369a1;">
                                        <div class="small fw-semibold">Izin</div>
                                        <div class="fs-5 fw-bold">{{ $presensiDetails->where('status_kehadiran', 'Izin')->count() }}</div>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="p-2 rounded text-center" style="background:#fee2e2; color:#b91c1c;">
                                        <div class="small fw-semibold">Alfa</div>
                                        <div class="fs-5 fw-bold">{{ $presensiDetails->where('status_kehadiran', 'Alfa')->count() }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;" class="text-center">No</th>
                                            <th>Nama Siswa</th>
                                            <th class="text-center" style="width: 100px;">Status</th>
                                            <th>Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($presensiDetails as $idx => $d)
                                            @php
                                                $chipClass = match(strtolower($d->status_kehadiran ?? '')) {
                                                    'hadir' => 'hadir',
                                                    'sakit' => 'sakit',
                                                    'izin'  => 'izin',
                                                    'alfa'  => 'alfa',
                                                    default => 'hadir'
                                                };
                                            @endphp
                                            <tr>
                                                <td class="text-center text-muted small">{{ $idx + 1 }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ $d->siswa->nama_siswa ?? ('Siswa ID: ' . $d->id_siswa) }}</div>
                                                    @if(!empty($d->siswa->nisn))
                                                        <div class="text-muted" style="font-size: 0.72rem;">NISN: {{ $d->siswa->nisn }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="presensi-chip {{ $chipClass }}">
                                                        {{ $d->status_kehadiran ?? 'Hadir' }}
                                                    </span>
                                                </td>
                                                <td class="small text-muted">{{ $d->keterangan ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection
