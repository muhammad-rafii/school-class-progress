@extends('layouts.app')

@section('title', 'Jadwal Pelajaran')
@section('page-title', 'Jadwal Pelajaran')
@section('breadcrumb', 'Akademik')
@section('meta_description', 'Kelola jadwal pelajaran dengan pengecekan anti-tabrakan ruangan, kelas, dan guru.')

@php
    $isLocked    = (bool) ($config->is_schedule_locked ?? false);
    $activeCycle = $config->active_cycle ?? '1';
    $hariList    = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

    // ==========================================================
    // DAFTAR JAM PELAJARAN (sesuai tabel waktu sekolah)
    // Format: [label, jam_mulai, jam_selesai]
    // Istirahat (IST) sengaja tidak dimasukkan karena bukan jam KBM.
    // Untuk menambah / mengubah jam, cukup edit baris di bawah ini.
    // ==========================================================
    $slotJam = [
        ['Jam ke-1',  '07:15', '07:55'],
        ['Jam ke-2',  '07:55', '08:35'],
        ['Jam ke-3',  '08:35', '09:15'],
        ['Jam ke-4',  '09:30', '10:10'],
        ['Jam ke-5',  '10:10', '10:50'],
        ['Jam ke-6',  '10:50', '11:30'],
        ['Jam ke-7',  '12:30', '13:10'],
        ['Jam ke-8',  '13:10', '13:50'],
        ['Jam ke-9',  '13:50', '14:30'],
        ['Jam ke-10', '14:30', '15:10'],
        ];
@endphp

@section('content')
    {{-- ===== Status bar ===== --}}
    <div class="info-strip">
        <span><i class="fa-solid fa-rotate me-1 text-primary"></i> Putaran aktif: <strong>{{ $activeCycle }}</strong></span>
        <span>
            <i class="fa-solid {{ $isLocked ? 'fa-lock text-danger' : 'fa-lock-open text-success' }} me-1"></i>
            Status jadwal: <strong>{{ $isLocked ? 'Terkunci' : 'Terbuka' }}</strong>
        </span>
        <span><i class="fa-solid fa-table-list me-1 text-secondary"></i> Total jadwal: <strong>{{ $jadwals->count() }}</strong></span>
    </div>

    <div class="row g-4">
        {{-- ===== Form Input Jadwal ===== --}}
        <div class="col-lg-5 col-xxl-4">
            <div class="card-modern sticky-lg">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-accent"><i class="fa-solid fa-calendar-plus"></i></div>
                    <div>
                        <h2>Input Jadwal Baru</h2>
                        <p>Sistem otomatis menguji bentrok jadwal</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    @if($isLocked)
                        <div class="alert-modern warning static mb-4">
                            <span class="alert-icon"><i class="fa-solid fa-lock"></i></span>
                            <div>Jadwal sedang <strong>dikunci</strong> oleh Waka Kurikulum. Input jadwal baru dinonaktifkan sementara.</div>
                        </div>
                    @endif

                    <form action="{{ route('jadwal.store') }}" method="POST" id="jadwalForm">
                        @csrf
                        <fieldset @disabled($isLocked)>
                            <div class="form-section-title">Data Akademik</div>

                            <div class="mb-3">
                                <label class="form-label" for="id_tahun_ajaran">Tahun Ajaran / Semester</label>
                                <select name="id_tahun_ajaran" id="id_tahun_ajaran" class="form-select" required>
                                    <option value="">-- Pilih Tahun Ajaran / Semester --</option>
                                    @foreach($tahunAjaran as $ta)
                                        <option value="{{ $ta->id_tahun_ajaran }}" {{ old('id_tahun_ajaran') == $ta->id_tahun_ajaran ? 'selected' : '' }}>
                                            {{ $ta->nama_semester ?? $ta->id_tahun_ajaran }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label" for="id_kelas">Kelas</label>
                                    <select name="id_kelas" id="id_kelas" class="form-select" required>
                                        <option value="">-- Pilih Kelas --</option>
                                        @foreach($kelas as $k)
                                            <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                                {{ $k->tingkat ?? '' }} {{ $k->id_jurusan ?? $k->nama_kelas ?? $k->id_kelas }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="id_ruangan_default">Ruangan</label>
                                    <select name="id_ruangan_default" id="id_ruangan_default" class="form-select" required>
                                        <option value="">-- Pilih Ruangan --</option>
                                        @foreach($ruangans as $r)
                                            <option value="{{ $r->id_ruangan }}" {{ old('id_ruangan_default') == $r->id_ruangan ? 'selected' : '' }}>
                                                {{ $r->nama_ruangan ?? 'Ruang '.$r->id_ruangan }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="id_mapel">Mata Pelajaran</label>
                                <select name="id_mapel" id="id_mapel" class="form-select" required>
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($mapels as $m)
                                        <option value="{{ $m->id_mapel }}" {{ old('id_mapel') == $m->id_mapel ? 'selected' : '' }}>
                                            {{ $m->nama_mapel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Guru Pengampu (otomatis terkunci jika login sebagai Guru) --}}
                            <div class="mb-4">
                                <label class="form-label" for="id_guru">Guru Pengampu</label>
                                @if(isset($guruLogin) && $guruLogin)
                                    <input type="hidden" name="id_guru" value="{{ $guruLogin->id_guru }}">
                                    <div class="input-icon">
                                        <i class="fa-solid fa-user-check"></i>
                                        <input type="text" id="id_guru" class="form-control" value="{{ $guruLogin->nama_guru }}" readonly>
                                    </div>
                                    <div class="form-text">Guru pengampu terisi otomatis sesuai akun Anda.</div>
                                @else
                                    <select name="id_guru" id="id_guru" class="form-select" required>
                                        <option value="">-- Pilih Guru --</option>
                                        @foreach($gurus as $g)
                                            <option value="{{ $g->id_guru }}" {{ old('id_guru') == $g->id_guru ? 'selected' : '' }}>
                                                {{ $g->nama_guru }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="form-section-title">Waktu</div>

                            <div class="mb-3">
                                <label class="form-label" for="hari">Hari</label>
                                <select name="hari" id="hari" class="form-select" required>
                                    <option value="">-- Pilih Hari --</option>
                                    @foreach($hariList as $h)
                                        <option value="{{ $h }}" {{ old('hari') == $h ? 'selected' : '' }}>{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <label class="form-label" for="jam_mulai">Jam Mulai</label>
                                    <select name="jam_mulai" id="jam_mulai" class="form-select" required>
                                        <option value="">-- Pilih Jam Mulai --</option>
                                        @foreach($slotJam as $sj)
                                            <option value="{{ $sj[1] }}" {{ old('jam_mulai') == $sj[1] ? 'selected' : '' }}>
                                                {{ $sj[0] }} ({{ str_replace(':', '.', $sj[1]) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="jam_selesai">Jam Selesai</label>
                                    <select name="jam_selesai" id="jam_selesai" class="form-select" required>
                                        <option value="">-- Pilih Jam Selesai --</option>
                                        @foreach($slotJam as $sj)
                                            <option value="{{ $sj[2] }}" {{ old('jam_selesai') == $sj[2] ? 'selected' : '' }}>
                                                {{ $sj[0] }} ({{ str_replace(':', '.', $sj[2]) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit" id="btnSaveJadwal" class="btn btn-accent w-100">
                                <i class="fa-solid {{ $isLocked ? 'fa-lock' : 'fa-shield-halved' }} me-1"></i>
                                {{ $isLocked ? 'Jadwal Dikunci' : 'Simpan & Uji Jadwal' }}
                            </button>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Daftar Jadwal ===== --}}
        <div class="col-lg-7 col-xxl-8">
            <div class="card-modern">
                <div class="card-modern-header flex-wrap">
                    <div class="icon-box solid tone-slate"><i class="fa-solid fa-table-list"></i></div>
                    <div>
                        <h2>Daftar Jadwal Aktif</h2>
                        <p>{{ $jadwals->count() }} jadwal terdaftar</p>
                    </div>
                    <div class="header-actions search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" class="form-control" id="searchJadwal" placeholder="Cari hari, kelas, mapel..." data-table-search="#tableJadwal">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern" id="tableJadwal">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Hari / Jam</th>
                                <th>Kelas &amp; Mapel</th>
                                <th>Guru &amp; Ruangan</th>
                                <th>Tahun Ajaran</th>
                                <th>Putaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jadwals as $index => $j)
                                <tr data-row>
                                    <td><span class="row-index">{{ $index + 1 }}</span></td>
                                    <td>
                                        <div class="cell-title">{{ $j->hari }}</div>
                                        <div class="cell-sub"><i class="fa-regular fa-clock me-1"></i>{{ substr($j->jam_mulai, 0, 5) }} – {{ substr($j->jam_selesai, 0, 5) }}</div>
                                    </td>
                                    <td>
                                        <div class="cell-title">{{ $j->kelas->nama_kelas ?? $j->id_kelas }}</div>
                                        <div class="cell-sub">{{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @php $gName = $j->guru->nama_guru ?? (string) $j->id_guru; @endphp
                                            <span class="mini-avatar">{{ strtoupper(mb_substr($gName, 0, 1)) }}</span>
                                            <div>
                                                <div class="cell-title">{{ $gName }}</div>
                                                <div class="cell-sub"><i class="fa-solid fa-door-open me-1"></i>{{ $j->ruangan->nama_ruangan ?? $j->id_ruangan_default }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge-soft neutral">{{ $j->tahunAjaran->nama_semester ?? $j->id_tahun_ajaran }}</span></td>
                                    <td><span class="badge-soft accent dot">{{ $j->cycle_type }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
                                            <h4>Belum ada jadwal</h4>
                                            <p>Belum ada data jadwal milik Anda. Tambahkan melalui form di samping.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            @if($jadwals->isNotEmpty())
                                <tr class="js-no-result d-none">
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                            <h4>Tidak ditemukan</h4>
                                            <p>Tidak ada jadwal yang cocok dengan pencarian.</p>
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