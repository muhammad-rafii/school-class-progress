@extends('layouts.app')

@section('title', 'Dashboard Guru')
@section('page-title', 'Dashboard')
@section('meta_description', 'Dashboard guru untuk mengisi jurnal mengajar dan melihat jadwal pelajaran.')

@php
    $isBk = (int) session('id_role') === 7;
    $hour = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
    {{-- ===== HERO ===== --}}
    <section class="page-hero mb-4">
        <i class="fa-solid {{ $isBk ? 'fa-hand-holding-heart' : 'fa-person-chalkboard' }} hero-illustration"></i>
        <span class="hero-eyebrow"><i class="fa-solid fa-sun"></i> {{ $greeting }}</span>
        <h2>Selamat datang, {{ session('nama_lengkap') ?: session('username') }}!</h2>
        <p>Catat kegiatan belajar mengajar harian dan pantau jadwal mengajar Anda dengan mudah.</p>
        <div class="hero-chips">
            <span class="hero-chip"><i class="fa-solid fa-id-badge"></i> {{ session('username') }}</span>
            <span class="hero-chip"><i class="fa-solid {{ $isBk ? 'fa-hand-holding-heart' : 'fa-person-chalkboard' }}"></i> {{ $isBk ? 'Guru BK' : 'Guru Pengampu' }}</span>
        </div>
    </section>

    {{-- ===== NOTIFIKASI JURNAL BELUM DIISI ===== --}}
    @if(!$isBk && isset($jurnalTerlambat) && $jurnalTerlambat->isNotEmpty())
        @php $masihBisaIsi = $jurnalTerlambat->contains(fn($t) => $t['status'] === 'segera'); @endphp
        <div class="alert-modern danger static mb-4" id="notif-jurnal-terlambat" role="alert">
            <span class="alert-icon"><i class="fa-solid fa-bell"></i></span>
            <div class="flex-grow-1">
                <strong>{{ $jurnalTerlambat->count() }} jurnal mengajar hari ini belum diisi</strong>
                <div class="small mb-2">Jam pelajaran sudah selesai. Jurnal hanya bisa diisi sampai {{ \App\Support\JurnalTerlambat::TOLERANSI_MENIT }} menit setelah jam selesai.</div>
                <ul class="mb-2">
                    @foreach($jurnalTerlambat as $t)
                        <li>
                            <strong>{{ $t['mapel'] }}</strong> &middot; {{ $t['kelas'] }}
                            ({{ $t['jam_mulai'] }}–{{ $t['jam_selesai'] }})
                            @if($t['status'] === 'segera')
                                <span class="text-danger fw-semibold">— segera isi, sisa {{ \App\Support\JurnalTerlambat::formatTelat($t['sisa_menit']) }} (batas {{ $t['batas'] }})</span>
                            @else
                                <span class="text-muted">— batas pengisian {{ $t['batas'] }} sudah lewat, selesai sejak {{ \App\Support\JurnalTerlambat::formatTelat($t['telat_menit']) }} lalu</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if($masihBisaIsi)
                    <a href="{{ route('jurnal.index') }}" class="btn btn-sm btn-danger">
                        <i class="fa-solid fa-book-open-reader me-1"></i> Isi Jurnal Sekarang
                    </a>
                @endif
            </div>
        </div>
    @endif

    <div class="section-heading">
        <h3>Menu Utama</h3>
        <span>Pilih modul yang ingin dibuka</span>
    </div>

    <div class="row g-4 animate-in">
        <div class="col-md-6 {{ $isBk ? 'col-xl-4' : '' }}">
            <a href="{{ route('jurnal.index') }}" id="quick-jurnal" class="action-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-book-open-reader"></i></div>
                <h4>Modul Jurnal Mengajar</h4>
                <p>Isi jurnal kegiatan belajar mengajar (KBM) harian dan presensi siswa secara real-time, lengkap dengan foto bukti.</p>
                <span class="action-link">Masuk Jurnal <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>
        <div class="col-md-6 {{ $isBk ? 'col-xl-4' : '' }}">
            <a href="{{ route('jadwal.index') }}" id="quick-jadwal" class="action-card tone-emerald">
                <div class="icon-box solid lg"><i class="fa-solid fa-calendar-days"></i></div>
                <h4>Jadwal Pelajaran</h4>
                <p>Lihat daftar jadwal mengajar lengkap beserta status putaran blok dan pengecekan anti-tabrakan.</p>
                <span class="action-link">Masuk Jadwal <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>
        @if($isBk)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('kesiswaan.izin.index') }}" id="quick-izin" class="action-card tone-amber">
                    <div class="icon-box solid lg"><i class="fa-solid fa-clipboard-check"></i></div>
                    <h4>Verifikasi Izin Siswa</h4>
                    <p>Tinjau dan setujui atau tolak pengajuan izin / sakit yang dikirim oleh siswa.</p>
                    <span class="action-link">Buka Verifikasi <i class="fa-solid fa-arrow-right"></i></span>
                </a>
            </div>
        @endif
    </div>

    <div class="info-strip mt-4 mb-0">
        <i class="fa-solid fa-lightbulb text-warning"></i>
        <span><strong>Tips:</strong> Isi jurnal setiap selesai mengajar agar rekap Waka Kurikulum selalu terbarui. Gunakan tombol <i class="fa-solid fa-bars-staggered"></i> untuk menciutkan sidebar.</span>
    </div>
@endsection