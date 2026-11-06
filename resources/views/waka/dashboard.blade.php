@extends('layouts.app')

@section('title', 'Dashboard Waka Kurikulum')
@section('page-title', 'Dashboard')
@section('meta_description', 'Panel Waka Kurikulum untuk mengatur siklus blok, penguncian jadwal, manajemen akun, dan rekap jurnal guru.')

@php
    $activeCycle = (string) ($config->active_cycle ?? '1');
    $isLocked    = (bool) ($config->is_schedule_locked ?? false);
@endphp

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="page-hero mb-4">
        <i class="fa-solid fa-user-tie hero-illustration"></i>
        <span class="hero-eyebrow"><i class="fa-solid fa-wand-magic-sparkles"></i> Panel Waka Kurikulum</span>
        <h2>Halo, {{ session('nama_lengkap') ?: session('username') }}</h2>
        <p>Kelola siklus blok pembelajaran, kunci jadwal, akun pengguna, dan pantau jurnal mengajar guru dari satu tempat.</p>
        <div class="hero-chips">
            <span class="hero-chip"><i class="fa-solid fa-rotate"></i> Putaran {{ $activeCycle }} aktif</span>
            <span class="hero-chip">
                <i class="fa-solid {{ $isLocked ? 'fa-lock' : 'fa-lock-open' }}"></i>
                Jadwal {{ $isLocked ? 'Terkunci' : 'Terbuka' }}
            </span>
        </div>
    </section>

    {{-- ===== STATUS ===== --}}
    <div class="row g-3 animate-in">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card tone-violet">
                <div class="icon-box solid lg"><i class="fa-solid fa-rotate"></i></div>
                <div>
                    <div class="stat-label">Putaran Blok</div>
                    <div class="stat-value">Putaran {{ $activeCycle }}</div>
                    <div class="stat-meta">Berlaku global</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card {{ $isLocked ? 'tone-rose' : 'tone-emerald' }}">
                <div class="icon-box solid lg"><i class="fa-solid {{ $isLocked ? 'fa-lock' : 'fa-lock-open' }}"></i></div>
                <div>
                    <div class="stat-label">Status Jadwal</div>
                    <div class="stat-value">{{ $isLocked ? 'Terkunci' : 'Terbuka' }}</div>
                    <div class="stat-meta">{{ $isLocked ? 'Guru tidak dapat mengubah' : 'Guru bebas mengubah' }}</div>
                </div>
            </div>
        </div>
        @if(!empty($config->start_date))
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card tone-blue">
                    <div class="icon-box solid lg"><i class="fa-solid fa-calendar-plus"></i></div>
                    <div>
                        <div class="stat-label">Mulai Siklus</div>
                        <div class="stat-value fs-5">{{ \Carbon\Carbon::parse($config->start_date)->locale('id')->translatedFormat('d M Y') }}</div>
                        <div class="stat-meta">Tanggal awal</div>
                    </div>
                </div>
            </div>
        @endif
        @if(!empty($config->end_date))
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card tone-amber">
                    <div class="icon-box solid lg"><i class="fa-solid fa-flag-checkered"></i></div>
                    <div>
                        <div class="stat-label">Akhir Siklus</div>
                        <div class="stat-value fs-5">{{ \Carbon\Carbon::parse($config->end_date)->locale('id')->translatedFormat('d M Y') }}</div>
                        <div class="stat-meta">Tanggal selesai</div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ===== NOTIFIKASI JURNAL BELUM DIISI ===== --}}
    @if(isset($jurnalTerlambat) && $jurnalTerlambat->isNotEmpty())
        @php $perGuru = $jurnalTerlambat->groupBy('id_guru'); @endphp
        <div class="alert-modern danger static mt-4 mb-0" id="notif-jurnal-terlambat" role="alert">
            <span class="alert-icon"><i class="fa-solid fa-bell"></i></span>
            <div class="flex-grow-1">
                <strong>{{ $jurnalTerlambat->count() }} jurnal dari {{ $perGuru->count() }} guru belum diisi hari ini</strong>
                <div class="small mb-2">Jam pelajaran sudah selesai tetapi jurnal belum diisi. Guru hanya bisa mengisi sampai {{ \App\Support\JurnalTerlambat::TOLERANSI_MENIT }} menit setelah jam selesai.</div>
                <div style="max-height: 260px; overflow-y: auto;">
                    @foreach($perGuru as $items)
                        <div class="mb-2">
                            <div class="fw-semibold">{{ $items->first()['guru'] }} <span class="badge-soft danger">{{ $items->count() }} jurnal</span></div>
                            <ul class="mb-0 small">
                                @foreach($items as $t)
                                    <li>{{ $t['mapel'] }} &middot; {{ $t['kelas'] }} ({{ $t['jam_mulai'] }}–{{ $t['jam_selesai'] }}) —
                                        @if($t['status'] === 'segera')
                                            <span class="text-danger">belum diisi, masih bisa diisi {{ \App\Support\JurnalTerlambat::formatTelat($t['sisa_menit']) }} lagi</span>
                                        @else
                                            <span class="text-muted">batas pengisian lewat, selesai {{ \App\Support\JurnalTerlambat::formatTelat($t['telat_menit']) }} lalu</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('waka.jurnal.rekap') }}" class="btn btn-sm btn-outline-danger mt-2">
                    <i class="fa-solid fa-book-bookmark me-1"></i> Buka Rekap Jurnal
                </a>
            </div>
        </div>
    @endif

    <div class="row g-4 mt-1">
        {{-- ===== AKSES CEPAT ===== --}}
        <div class="col-lg-7">
            <div class="section-heading mt-2">
                <h3>Akses Cepat</h3>
                <span>Menu utama Waka Kurikulum</span>
            </div>
            <div class="row g-3 animate-in">
                <div class="col-md-6">
                    <a href="{{ route('akun.index') }}" id="quick-akun" class="action-card tone-violet">
                        <div class="icon-box solid lg"><i class="fa-solid fa-users-gear"></i></div>
                        <h4>Manajemen Akun</h4>
                        <p>Buat akun baru dan tentukan hak akses (Waka, Guru, Siswa).</p>
                        <span class="action-link">Kelola Akun <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('waka.jurnal.rekap') }}" id="quick-rekap" class="action-card tone-emerald">
                        <div class="icon-box solid lg"><i class="fa-solid fa-book-bookmark"></i></div>
                        <h4>Rekap Jurnal Guru</h4>
                        <p>Pantau jurnal kegiatan belajar mengajar dari seluruh guru.</p>
                        <span class="action-link">Lihat Rekap <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                </div>
                <div class="col-12">
                    <a href="{{ route('jadwal.index') }}" id="quick-jadwal" class="action-card tone-blue flex-md-row align-items-md-center gap-md-4">
                        <div class="icon-box solid lg"><i class="fa-solid fa-calendar-days"></i></div>
                        <div class="flex-grow-1">
                            <h4 class="mt-3 mt-md-0">Jadwal Pelajaran</h4>
                            <p class="mb-3 mb-md-0">Kelola jadwal dengan pengecekan anti-tabrakan ruangan, kelas, dan guru.</p>
                        </div>
                        <span class="action-link mt-md-0">Buka Jadwal <i class="fa-solid fa-arrow-right"></i></span>
                    </a>
                </div>
            </div>
        </div>

        {{-- ===== KONFIGURASI ===== --}}
        <div class="col-lg-5">
            <div class="section-heading mt-2">
                <h3>Konfigurasi Sistem</h3>
                <span>Berlaku untuk semua pengguna</span>
            </div>
            <div class="card-modern">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-accent"><i class="fa-solid fa-sliders"></i></div>
                    <div>
                        <h2>Siklus Blok &amp; Kunci Jadwal</h2>
                        <p>Atur putaran aktif dan izin edit jadwal</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    <form action="{{ route('waka.config.update') }}" method="POST" id="configForm">
                        @csrf

                        <div class="form-section-title">Putaran Blok Aktif</div>
                        <div class="option-grid mb-2">
                            <label class="option-card tone-violet">
                                <input type="radio" name="active_cycle" value="1" {{ $activeCycle === '1' ? 'checked' : '' }} required>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-1"></i></span>
                                    <span>
                                        <span class="option-title d-block">Putaran 1</span>
                                        <span class="option-desc d-block">Blok pertama</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                            <label class="option-card tone-violet">
                                <input type="radio" name="active_cycle" value="2" {{ $activeCycle === '2' ? 'checked' : '' }}>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-2"></i></span>
                                    <span>
                                        <span class="option-title d-block">Putaran 2</span>
                                        <span class="option-desc d-block">Blok kedua</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                        </div>
                        <div class="form-text mb-4">Mengubah ini akan menyesuaikan tampilan jadwal secara global di sistem.</div>

                        <div class="form-section-title">Penguncian Jadwal</div>
                        <div class="option-grid mb-2">
                            <label class="option-card tone-emerald">
                                <input type="radio" name="is_schedule_locked" value="0" {{ !$isLocked ? 'checked' : '' }} required>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-lock-open"></i></span>
                                    <span>
                                        <span class="option-title d-block">Buka</span>
                                        <span class="option-desc d-block">Guru bebas edit</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                            <label class="option-card tone-rose">
                                <input type="radio" name="is_schedule_locked" value="1" {{ $isLocked ? 'checked' : '' }}>
                                <span class="option-body">
                                    <span class="icon-box"><i class="fa-solid fa-lock"></i></span>
                                    <span>
                                        <span class="option-title d-block">Kunci</span>
                                        <span class="option-desc d-block">Guru dilarang ubah</span>
                                    </span>
                                    <span class="option-check"><i class="fa-solid fa-check"></i></span>
                                </span>
                            </label>
                        </div>
                        <div class="form-text mb-4">Jika dikunci, tombol aksi input/edit jadwal guru akan dinonaktifkan.</div>

                        <button type="submit" id="btnSaveConfig" class="btn btn-accent w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan Konfigurasi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection