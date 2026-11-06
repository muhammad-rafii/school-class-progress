@extends('layouts.app')

@section('title', 'Jurnal Mengajar & Presensi')
@section('page-title', 'Jurnal Mengajar & Presensi Siswa')
@section('breadcrumb', 'Kegiatan Mengajar')
@section('meta_description', 'Isi jurnal kegiatan belajar mengajar harian lengkap dengan presensi siswa dan foto bukti KBM.')

@php
    $fmtDate   = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('id')->translatedFormat('d M Y') : '-';
    $isImage   = fn ($p) => $p && preg_match('/\.(jpe?g|png|gif|webp)$/i', $p);
    $jurnalSorted  = $jurnalList->sortByDesc('date')->values();
    $jurnalHariIni = $jurnalList->filter(fn ($j) => $j->date && \Carbon\Carbon::parse($j->date)->isToday())->count();
@endphp

@section('content')
    {{-- ===== STATUS ===== --}}
    <div class="row g-3 mb-4 animate-in">
        <div class="col-sm-6 col-xl-4">
            <div class="stat-card tone-blue">
                <div class="icon-box solid lg"><i class="fa-solid fa-book-open-reader"></i></div>
                <div>
                    <div class="stat-label">Total Jurnal</div>
                    <div class="stat-value">{{ $jurnalList->count() }}</div>
                    <div class="stat-meta">Seluruh jurnal tercatat</div>
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
                <div class="icon-box solid lg"><i class="fa-solid fa-calendar-days"></i></div>
                <div>
                    <div class="stat-label">Jadwal Mengajar</div>
                    <div class="stat-value">{{ $jadwals->count() }}</div>
                    <div class="stat-meta">Jadwal milik {{ $guru->nama_guru ?? 'Anda' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ===== Form Input Jurnal & Presensi ===== --}}
        <div class="col-lg-5 col-xxl-5">
            <div class="card-modern sticky-lg">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-accent"><i class="fa-solid fa-pen-to-square"></i></div>
                    <div>
                        <h2>Input Jurnal &amp; Presensi</h2>
                        <p>Catat KBM, presensi siswa &amp; unggah bukti</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    @if($jadwalHariIni->isEmpty() && $jumlahSudahIsi == 0 && !$jadwals->isEmpty())
                        <div class="alert-modern warning static mb-4">
                            <span class="alert-icon"><i class="fa-solid fa-calendar-xmark"></i></span>
                            <div>Anda tidak memiliki jadwal mengajar pada hari ini ({{ $hariIni ?? 'Minggu' }}), sehingga jurnal belum bisa diisi. Jurnal hanya dapat diisi untuk jadwal hari ini.</div>
                        </div>
                    @endif
                    @if($jadwalHariIni->isEmpty() && $jumlahSudahIsi > 0)
                        <div class="alert-modern warning static mb-4">
                            <span class="alert-icon"><i class="fa-solid fa-circle-check"></i></span>
                            <div>Semua jadwal hari ini sudah terisi jurnalnya. Jika ada yang perlu diperbaiki, gunakan fitur edit presensi pada riwayat jurnal.</div>
                        </div>
                    @endif
                    @if($jadwals->isEmpty())
                        <div class="alert-modern warning static mb-4">
                            <span class="alert-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                            <div>Anda belum memiliki jadwal. Tambahkan dulu melalui menu <a href="{{ route('jadwal.index') }}" class="fw-bold">Jadwal Pelajaran</a>.</div>
                        </div>
                    @endif

                    <form action="{{ route('jurnal.store') }}" method="POST" enctype="multipart/form-data" id="jurnalForm">
                        @csrf
                        <div class="form-section-title">1. Jadwal Pelajaran</div>
                        <div class="mb-4">
                            <label class="form-label" for="schedule_id">Pilih Jadwal Mengajar <span class="text-muted small fw-normal">(hanya jadwal hari ini, saat jam pelajaran berlangsung)</span> <span class="text-danger">*</span></label>
                            <select name="schedule_id" id="schedule_id" class="form-select" required>
                                <option value="">{{ $jadwalHariIni->isEmpty() ? '-- Tidak ada jadwal yang dapat diisi --' : '-- Pilih Jadwal Hari Ini (' . $hariIni . ') --' }}</option>
                                @foreach($jadwalHariIni as $j)
                                    @php
                                        $labelKelasJadwal = $j->kelas 
                                            ? trim(($j->kelas->tingkat ?? '') . ' ' . ($j->kelas->id_jurusan ?? $j->kelas->nama_kelas ?? $j->kelas->id_kelas)) 
                                            : ($j->id_kelas ?? '-');
                                    @endphp
                                    @php $stWaktu = $statusWaktuMap[$j->id_jadwal] ?? 'boleh'; @endphp
                                    <option value="{{ $j->id_jadwal }}" 
                                            @disabled($stWaktu !== 'boleh')
                                            data-kelas="{{ $j->id_kelas }}" 
                                            data-kelas-nama="{{ $labelKelasJadwal }}"
                                            {{ old('schedule_id') == $j->id_jadwal ? 'selected' : '' }}>
                                        {{ $j->hari }}, {{ substr($j->jam_mulai, 0, 5) }}–{{ substr($j->jam_selesai, 0, 5) }}
                                        · {{ $labelKelasJadwal }}
                                        · {{ $j->mataPelajaran->nama_mapel ?? $j->id_mapel }}
                                        ({{ $j->cycle_type }})
                                        @if($stWaktu === 'belum_mulai') — belum dimulai @elseif($stWaktu === 'lewat') — waktu pengisian habis @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-section-title">2. Kegiatan Belajar Mengajar</div>
                        <div class="mb-3">
                            <label class="form-label" for="topic_description">Deskripsi Topik / Materi <span class="text-danger">*</span></label>
                            <textarea name="topic_description" id="topic_description" class="form-control" rows="3" required placeholder="Tuliskan materi pembelajaran hari ini...">{{ old('topic_description') }}</textarea>
                        </div>

                        {{-- ===== FOTO BUKTI KBM: HANYA DARI KAMERA (REAL-TIME) ===== --}}
                        <div class="mb-4" id="photoCapture" data-server-now="{{ now()->timestamp * 1000 }}">
                            <label class="form-label">Foto Bukti KBM <span class="text-danger">*</span></label>
                            <input type="file" name="photo" id="photo" class="d-none" accept="image/jpeg,image/png">
                            <input type="hidden" name="photo_source" id="photo_source" value="">

                            <div class="border rounded-3 p-3 bg-light">
                                <div id="camStage" class="d-none mb-2">
                                    <video id="camVideo" autoplay playsinline muted class="w-100 rounded-3 bg-dark" style="max-height: 320px; object-fit: cover;"></video>
                                </div>
                                <div class="upload-preview mb-2" id="photoPreview"></div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" id="btnOpenCam" class="btn btn-primary btn-sm"><i class="fa-solid fa-camera me-1"></i> Buka Kamera</button>
                                    <button type="button" id="btnSnap" class="btn btn-success btn-sm d-none"><i class="fa-solid fa-circle-dot me-1"></i> Ambil Foto</button>
                                    <button type="button" id="btnCancelCam" class="btn btn-outline-secondary btn-sm d-none">Batal</button>
                                    <button type="button" id="btnRetake" class="btn btn-outline-primary btn-sm d-none"><i class="fa-solid fa-rotate-right me-1"></i> Foto Ulang</button>
                                    <label id="btnFallback" class="btn btn-primary btn-sm d-none mb-0">
                                        <i class="fa-solid fa-camera me-1"></i> Ambil dengan Kamera HP
                                        <input type="file" id="photoFallback" accept="image/*" capture="environment" class="d-none">
                                    </label>
                                </div>
                                <div class="form-text">Foto harus diambil langsung dari kamera saat ini. Unggah dari galeri/berkas tidak diizinkan. Cap tanggal &amp; jam otomatis ditambahkan pada foto.</div>
                                <div class="text-danger small mt-1 d-none" id="camError"></div>
                            </div>
                            <canvas id="camCanvas" class="d-none"></canvas>
                        </div>

                        <script>
                            (function () {
                                const wrap = document.getElementById('photoCapture');
                                if (!wrap) return;
                                const form = document.getElementById('jurnalForm');
                                const input = document.getElementById('photo');
                                const source = document.getElementById('photo_source');
                                const video = document.getElementById('camVideo');
                                const stage = document.getElementById('camStage');
                                const canvas = document.getElementById('camCanvas');
                                const preview = document.getElementById('photoPreview');
                                const errBox = document.getElementById('camError');
                                const btnOpen = document.getElementById('btnOpenCam');
                                const btnSnap = document.getElementById('btnSnap');
                                const btnCancel = document.getElementById('btnCancelCam');
                                const btnRetake = document.getElementById('btnRetake');
                                const btnFallback = document.getElementById('btnFallback');
                                const fallbackInput = document.getElementById('photoFallback');
                                const isTouch = window.matchMedia('(pointer: coarse)').matches;
                                let stream = null;

                                // Jam server (bukan jam perangkat) dipakai untuk cap waktu pada foto
                                const serverOffset = Number(wrap.dataset.serverNow) - Date.now();
                                const nowServer = () => new Date(Date.now() + serverOffset);

                                const show = (el, on) => el.classList.toggle('d-none', !on);
                                const setError = (msg) => { errBox.textContent = msg || ''; show(errBox, !!msg); };

                                function stopCam() {
                                    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
                                    video.srcObject = null;
                                    show(stage, false); show(btnSnap, false); show(btnCancel, false);
                                }

                                function showIdle(hasPhoto) {
                                    show(btnOpen, !hasPhoto);
                                    show(btnRetake, hasPhoto);
                                }

                                function stamp(ctx, w, h) {
                                    const teks = nowServer().toLocaleString('id-ID', {
                                        timeZone: 'Asia/Jakarta', weekday: 'long', day: 'numeric', month: 'long',
                                        year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false
                                    }) + ' WIB';
                                    const fs = Math.max(14, Math.round(w / 40));
                                    ctx.font = 'bold ' + fs + 'px sans-serif';
                                    ctx.fillStyle = 'rgba(0,0,0,.55)';
                                    ctx.fillRect(0, h - fs * 2, w, fs * 2);
                                    ctx.fillStyle = '#fff';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(teks, fs * 0.6, h - fs);
                                }

                                function finalize(drawFn, srcW, srcH) {
                                    const maxW = 1280;
                                    const scale = Math.min(1, maxW / srcW);
                                    const w = Math.round(srcW * scale), h = Math.round(srcH * scale);
                                    canvas.width = w; canvas.height = h;
                                    const ctx = canvas.getContext('2d');
                                    drawFn(ctx, w, h);
                                    stamp(ctx, w, h);
                                    canvas.toBlob((blob) => {
                                        if (!blob) { setError('Gagal memproses foto. Coba ambil ulang.'); return; }
                                        const file = new File([blob], 'jurnal_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                                        const dt = new DataTransfer();
                                        dt.items.add(file);
                                        input.files = dt.files;
                                        source.value = 'camera';
                                        const url = URL.createObjectURL(blob);
                                        preview.innerHTML = '<img alt="Foto KBM" src="' + url + '">';
                                        preview.classList.add('show');
                                        setError('');
                                        showIdle(true);
                                    }, 'image/jpeg', 0.8);
                                }

                                async function openCam() {
                                    setError('');
                                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                                        setError('Kamera tidak bisa diakses di alamat ini. Buka aplikasi lewat localhost atau HTTPS' + (isTouch ? ', atau gunakan tombol Kamera HP.' : '.'));
                                        if (isTouch) show(btnFallback, true);
                                        return;
                                    }
                                    try {
                                        stream = await navigator.mediaDevices.getUserMedia({
                                            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 } },
                                            audio: false
                                        });
                                        video.srcObject = stream;
                                        show(stage, true); show(btnSnap, true); show(btnCancel, true);
                                        show(btnOpen, false); show(btnRetake, false);
                                    } catch (e) {
                                        setError('Izin kamera ditolak atau kamera tidak ditemukan. Izinkan akses kamera pada browser lalu coba lagi.');
                                        if (isTouch) show(btnFallback, true);
                                    }
                                }

                                btnOpen.addEventListener('click', openCam);
                                btnRetake.addEventListener('click', () => {
                                    input.value = ''; source.value = '';
                                    preview.innerHTML = ''; preview.classList.remove('show');
                                    openCam();
                                });
                                btnCancel.addEventListener('click', () => { stopCam(); showIdle(input.files.length > 0); });
                                btnSnap.addEventListener('click', () => {
                                    if (!video.videoWidth) { setError('Kamera belum siap, tunggu sebentar.'); return; }
                                    finalize((ctx, w, h) => ctx.drawImage(video, 0, 0, w, h), video.videoWidth, video.videoHeight);
                                    stopCam();
                                });

                                // Cadangan khusus HP (jika getUserMedia tidak tersedia): kamera bawaan via capture
                                fallbackInput.addEventListener('change', () => {
                                    const f = fallbackInput.files && fallbackInput.files[0];
                                    if (!f) return;
                                    const img = new Image();
                                    img.onload = () => {
                                        finalize((ctx, w, h) => ctx.drawImage(img, 0, 0, w, h), img.naturalWidth, img.naturalHeight);
                                        URL.revokeObjectURL(img.src);
                                        fallbackInput.value = '';
                                    };
                                    img.onerror = () => setError('File foto tidak dapat dibaca.');
                                    img.src = URL.createObjectURL(f);
                                });

                                // Wajib ada foto dari kamera sebelum jurnal dikirim
                                form.addEventListener('submit', (e) => {
                                    if (!input.files.length || source.value !== 'camera') {
                                        e.preventDefault();
                                        setError('Foto bukti KBM wajib diambil langsung dari kamera.');
                                        wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    }
                                });

                                window.addEventListener('pagehide', stopCam);
                            })();
                        </script>

                        {{-- ===== BAGIAN PRESENSI SISWA ===== --}}
                        <div class="form-section-title d-flex justify-content-between align-items-center">
                            <span>3. Presensi Siswa</span>
                            <span class="badge bg-secondary-subtle text-secondary border fw-normal" id="labelKelasTerpilih">Pilih Jadwal</span>
                        </div>

                        <div id="placeholderJadwal" class="alert alert-light border text-muted small text-center p-3 mb-4">
                            <i class="fa-solid fa-arrow-up me-1 text-primary"></i> Silakan pilih jadwal mengajar di atas untuk memuat siswa kelas terkait.
                        </div>

                        <div id="sectionPresensi" class="d-none mb-4">
                            {{-- Bar Aksi Cepat & Rekap Mini --}}
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 p-2 bg-light rounded border">
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-success py-1 px-2" id="btnSemuaHadir" title="Setel semua siswa menjadi Hadir">
                                        <i class="fa-solid fa-check-double me-1"></i> Semua Hadir
                                    </button>
                                </div>
                                <div class="d-flex gap-1" style="font-size: 0.75rem;">
                                    <span class="badge bg-success" title="Total Hadir">H: <span id="countHadir">0</span></span>
                                    <span class="badge bg-warning text-dark" title="Total Sakit">S: <span id="countSakit">0</span></span>
                                    <span class="badge bg-info text-dark" title="Total Izin">I: <span id="countIzin">0</span></span>
                                    <span class="badge bg-danger" title="Total Alfa">A: <span id="countAlfa">0</span></span>
                                </div>
                            </div>

                            {{-- Filter Pencarian Siswa --}}
                            <div class="mb-2">
                                <input type="search" id="searchSiswaInput" class="form-control form-control-sm" placeholder="Cari nama / NISN siswa...">
                            </div>

                            {{-- Wadah Tabel Siswa Scrollable --}}
                            <div class="border rounded" style="max-height: 380px; overflow-y: auto; background: #fff;">
                                <table class="table table-sm table-hover align-middle mb-0" id="tablePresensiForm">
                                    <thead class="table-light sticky-top" style="top: 0; z-index: 2;">
                                        <tr style="font-size: 0.8rem;">
                                            <th style="width: 40%;">Siswa</th>
                                            <th style="width: 38%;" class="text-center">Status</th>
                                            <th style="width: 22%;">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($siswaList as $siswa)
                                            @php
                                                $leave = $todayLeaves->get($siswa->id_siswa);
                                                $defaultStatus = 'Hadir';
                                                $defaultKet = '';
                                                if ($leave) {
                                                    $isSakit = preg_match('/sakit/i', $leave->reason);
                                                    $defaultStatus = $isSakit ? 'Sakit' : 'Izin';
                                                    $defaultKet = 'Izin resmi (' . $leave->reason . ')';
                                                }
                                                $siswaKelas = !empty($siswa->id_kelas) ? $siswa->id_kelas : 'BELUM_ADA_KELAS';
                                            @endphp
                                            <tr class="row-siswa" 
                                                data-siswa-id="{{ $siswa->id_siswa }}"
                                                data-kelas="{{ $siswaKelas }}"
                                                data-nama="{{ strtolower($siswa->nama_siswa) }}"
                                                data-nisn="{{ $siswa->nisn }}">
                                                <td>
                                                    <div class="fw-semibold text-truncate" style="max-width: 140px;" title="{{ $siswa->nama_siswa }}">
                                                        {{ $siswa->nama_siswa }}
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.7rem;">
                                                        <span>NISN: {{ $siswa->nisn }}</span>
                                                        @if(!empty($siswa->id_kelas))
                                                            <span class="badge bg-secondary-subtle text-secondary py-0 px-1" style="font-size: 0.65rem;">{{ $siswa->kelas->nama_kelas ?? $siswa->id_kelas }}</span>
                                                        @endif
                                                    </div>
                                                    @if($leave)
                                                        <span class="badge bg-warning-subtle text-warning-emphasis border mt-1" style="font-size: 0.65rem;" title="{{ $leave->reason }}">
                                                            <i class="fa-solid fa-file-medical me-1"></i>Izin Resmi
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm w-100" role="group">
                                                        <input type="radio" class="btn-check presensi-radio" 
                                                               name="presensi[{{ $siswa->id_siswa }}][status]" 
                                                               id="st_{{ $siswa->id_siswa }}_H" 
                                                               value="Hadir" 
                                                               {{ $defaultStatus === 'Hadir' ? 'checked' : '' }} 
                                                               autocomplete="off" disabled>
                                                        <label class="btn btn-outline-success py-1 px-1" for="st_{{ $siswa->id_siswa }}_H" title="Hadir">H</label>

                                                        <input type="radio" class="btn-check presensi-radio" 
                                                               name="presensi[{{ $siswa->id_siswa }}][status]" 
                                                               id="st_{{ $siswa->id_siswa }}_S" 
                                                               value="Sakit" 
                                                               {{ $defaultStatus === 'Sakit' ? 'checked' : '' }} 
                                                               autocomplete="off" disabled>
                                                        <label class="btn btn-outline-warning py-1 px-1" for="st_{{ $siswa->id_siswa }}_S" title="Sakit">S</label>

                                                        <input type="radio" class="btn-check presensi-radio" 
                                                               name="presensi[{{ $siswa->id_siswa }}][status]" 
                                                               id="st_{{ $siswa->id_siswa }}_I" 
                                                               value="Izin" 
                                                               {{ $defaultStatus === 'Izin' ? 'checked' : '' }} 
                                                               autocomplete="off" disabled>
                                                        <label class="btn btn-outline-info py-1 px-1" for="st_{{ $siswa->id_siswa }}_I" title="Izin">I</label>

                                                        <input type="radio" class="btn-check presensi-radio" 
                                                               name="presensi[{{ $siswa->id_siswa }}][status]" 
                                                               id="st_{{ $siswa->id_siswa }}_A" 
                                                               value="Alfa" 
                                                               {{ $defaultStatus === 'Alfa' ? 'checked' : '' }} 
                                                               autocomplete="off" disabled>
                                                        <label class="btn btn-outline-danger py-1 px-1" for="st_{{ $siswa->id_siswa }}_A" title="Alfa">A</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" 
                                                           name="presensi[{{ $siswa->id_siswa }}][keterangan]" 
                                                           class="form-control form-control-sm py-1 px-1" 
                                                           placeholder="Catatan..." 
                                                           value="{{ $defaultKet }}"
                                                           style="font-size: 0.75rem;" disabled>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">Tidak ada data siswa aktif.</td>
                                            </tr>
                                        @endforelse
                                        <tr id="emptyKelasSiswa" class="d-none">
                                            <td colspan="3" class="text-center text-muted py-3">
                                                Tidak ditemukan siswa untuk kelas ini.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <script>
                            // Cegah klik ganda: nonaktifkan tombol setelah form benar-benar dikirim
                            (function () {
                                const f = document.getElementById('jurnalForm');
                                const b = document.getElementById('btnSaveJurnal');
                                if (!f || !b) return;
                                f.addEventListener('submit', function (e) {
                                    if (e.defaultPrevented) return;
                                    setTimeout(function () {
                                        b.disabled = true;
                                        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
                                    }, 0);
                                });
                            })();
                        </script>
                        <button type="submit" id="btnSaveJurnal" class="btn btn-accent w-100" @disabled($jadwals->isEmpty())>
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Jurnal &amp; Presensi
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== Riwayat Jurnal ===== --}}
        <div class="col-lg-7 col-xxl-7">
            <div class="card-modern">
                <div class="card-modern-header flex-wrap">
                    <div class="icon-box solid tone-slate"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    <div>
                        <h2>Riwayat Jurnal Mengajar</h2>
                        <p>{{ $jurnalList->count() }} jurnal tercatat</p>
                    </div>
                    <div class="header-actions search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" class="form-control" id="searchJurnal" placeholder="Cari kelas, mapel, topik..." data-table-search="#tableJurnal">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern" id="tableJurnal">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kelas &amp; Mapel</th>
                                <th>Topik</th>
                                <th>Presensi</th>
                                <th class="text-center">Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jurnalSorted as $index => $item)
                                <tr data-row>
                                    <td><span class="row-index">{{ $index + 1 }}</span></td>
                                    <td>
                                        <div class="cell-title text-nowrap">{{ $fmtDate($item->date) }}</div>
                                        @if($item->jadwal)
                                            <div class="cell-sub text-nowrap"><i class="fa-regular fa-clock me-1"></i>{{ $item->jadwal->hari }}, {{ substr($item->jadwal->jam_mulai, 0, 5) }}–{{ substr($item->jadwal->jam_selesai, 0, 5) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->jadwal)
                                            <div class="cell-title">{{ $item->jadwal->kelas->nama_kelas ?? $item->jadwal->id_kelas }}</div>
                                            <div class="cell-sub">{{ $item->jadwal->mataPelajaran->nama_mapel ?? $item->jadwal->id_mapel }}</div>
                                            <span class="badge-soft accent dot mt-1">Putaran {{ $item->jadwal->cycle_type }}</span>
                                        @else
                                            <span class="badge-soft neutral">Jadwal dihapus</span>
                                        @endif
                                    </td>
                                    <td><div class="text-clamp">{{ $item->topic_description }}</div></td>
                                    <td>
                                        @if(isset($item->presensi) && $item->presensi && $item->presensi->details->isNotEmpty())
                                            @php
                                                $hadir = $item->presensi->details->where('status_kehadiran', 'Hadir')->count();
                                                $sakit = $item->presensi->details->where('status_kehadiran', 'Sakit')->count();
                                                $izin  = $item->presensi->details->where('status_kehadiran', 'Izin')->count();
                                                $alfa  = $item->presensi->details->where('status_kehadiran', 'Alfa')->count();
                                                $modalId = 'modalPresensi_' . ($item->id ?? $index);
                                                $bisaEdit = \Carbon\Carbon::parse($item->date)->toDateString() === $today;
                                            @endphp
                                            <div class="d-flex flex-wrap gap-1 align-items-center mb-1" style="font-size: 0.72rem;">
                                                <span class="badge bg-success" title="Hadir">H: {{ $hadir }}</span>
                                                @if($sakit > 0)<span class="badge bg-warning text-dark" title="Sakit">S: {{ $sakit }}</span>@endif
                                                @if($izin > 0)<span class="badge bg-info text-dark" title="Izin">I: {{ $izin }}</span>@endif
                                                @if($alfa > 0)<span class="badge bg-danger" title="Alfa">A: {{ $alfa }}</span>@endif
                                            </div>
                                            @if($bisaEdit)
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i>Presensi / Edit
                                                </button>
                                            @else
                                                <span class="badge-soft neutral" style="font-size: 0.72rem;" title="Jurnal hanya bisa diedit pada hari yang sama">
                                                    <i class="fa-solid fa-lock me-1"></i>Terkunci
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge-soft neutral" style="font-size: 0.72rem;">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isImage($item->photo_path))
                                            <a href="{{ asset($item->photo_path) }}" target="_blank" class="thumb" title="Lihat foto">
                                                <img src="{{ asset($item->photo_path) }}" alt="Foto KBM" loading="lazy">
                                            </a>
                                        @elseif($item->photo_path)
                                            <a href="{{ asset($item->photo_path) }}" target="_blank" class="file-chip"><i class="fa-solid fa-file"></i> Lihat</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-regular fa-file-lines"></i></div>
                                            <h4>Belum ada jurnal</h4>
                                            <p>Isi jurnal pertama Anda melalui form di samping.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                            @if($jurnalList->isNotEmpty())
                                <tr class="js-no-result d-none">
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                                            <h4>Tidak ditemukan</h4>
                                            <p>Tidak ada jurnal yang cocok dengan pencarian.</p>
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

    {{-- ===== MODAL DETAIL & EDIT PRESENSI SISWA ===== --}}
    @foreach($jurnalSorted as $index => $item)
        @if(isset($item->presensi) && $item->presensi && $item->presensi->details->isNotEmpty() && \Carbon\Carbon::parse($item->date)->toDateString() === $today)
            @php
                $modalId = 'modalPresensi_' . ($item->id ?? $index);
                $hadirCount = $item->presensi->details->where('status_kehadiran', 'Hadir')->count();
                $sakitCount = $item->presensi->details->where('status_kehadiran', 'Sakit')->count();
                $izinCount  = $item->presensi->details->where('status_kehadiran', 'Izin')->count();
                $alfaCount  = $item->presensi->details->where('status_kehadiran', 'Alfa')->count();
            @endphp
            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content shadow border-0">
                        <form action="{{ route('jurnal.presensi.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id_presensi" value="{{ $item->presensi->id_presensi }}">
                            
                            <div class="modal-header bg-light">
                                <div>
                                    <h6 class="modal-title fw-bold" id="{{ $modalId }}Label">
                                        <i class="fa-solid fa-clipboard-user text-primary me-2"></i>
                                        Presensi: {{ $item->jadwal->kelas->nama_kelas ?? $item->jadwal->id_kelas ?? 'Kelas' }}
                                    </h6>
                                    <div class="text-muted small">
                                        {{ $fmtDate($item->date) }} · {{ $item->jadwal->mataPelajaran->nama_mapel ?? '-' }}
                                    </div>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body p-0">
                                {{-- Ringkasan Badge --}}
                                <div class="p-3 bg-light-subtle border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="small fw-semibold text-muted">
                                        <i class="fa-solid fa-pen-to-square me-1 text-primary"></i> Anda dapat mengubah kehadiran siswa di bawah ini:
                                    </div>
                                    <div class="d-flex gap-1" style="font-size: 0.75rem;">
                                        <span class="badge bg-success">H: {{ $hadirCount }}</span>
                                        <span class="badge bg-warning text-dark">S: {{ $sakitCount }}</span>
                                        <span class="badge bg-info text-dark">I: {{ $izinCount }}</span>
                                        <span class="badge bg-danger">A: {{ $alfaCount }}</span>
                                    </div>
                                </div>

                                {{-- Catatan Topik KBM --}}
                                <div class="p-3 border-bottom bg-white">
                                    <label class="form-label small fw-bold mb-1" for="topic_desc_{{ $item->presensi->id_presensi }}">Topik / Catatan KBM</label>
                                    <input type="text" name="catatan_kbm" id="topic_desc_{{ $item->presensi->id_presensi }}" class="form-control form-control-sm" value="{{ $item->presensi->catatan_kbm ?? $item->topic_description }}">
                                </div>

                                {{-- Daftar Siswa & Pilihan Presensi --}}
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top" style="top: 0; z-index: 1;">
                                            <tr style="font-size: 0.8rem;">
                                                <th style="width: 40%;">Siswa</th>
                                                <th style="width: 35%;" class="text-center">Status</th>
                                                <th style="width: 25%;">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($item->presensi->details as $d)
                                                @php
                                                    $curStatus = $d->status_kehadiran;
                                                @endphp
                                                <tr style="font-size: 0.85rem;">
                                                    <td class="py-2">
                                                        <div class="fw-semibold">{{ $d->siswa->nama_siswa ?? 'Siswa #' . $d->id_siswa }}</div>
                                                        <div class="text-muted" style="font-size: 0.72rem;">NISN: {{ $d->siswa->nisn ?? '-' }}</div>
                                                    </td>
                                                    <td class="text-center py-2">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <input type="radio" class="btn-check" 
                                                                   name="presensi[{{ $d->id_siswa }}][status]" 
                                                                   id="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_H" 
                                                                   value="Hadir" 
                                                                   {{ $curStatus === 'Hadir' ? 'checked' : '' }} autocomplete="off">
                                                            <label class="btn btn-outline-success py-1 px-2" for="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_H" title="Hadir">H</label>

                                                            <input type="radio" class="btn-check" 
                                                                   name="presensi[{{ $d->id_siswa }}][status]" 
                                                                   id="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_S" 
                                                                   value="Sakit" 
                                                                   {{ $curStatus === 'Sakit' ? 'checked' : '' }} autocomplete="off">
                                                            <label class="btn btn-outline-warning py-1 px-2" for="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_S" title="Sakit">S</label>

                                                            <input type="radio" class="btn-check" 
                                                                   name="presensi[{{ $d->id_siswa }}][status]" 
                                                                   id="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_I" 
                                                                   value="Izin" 
                                                                   {{ $curStatus === 'Izin' ? 'checked' : '' }} autocomplete="off">
                                                            <label class="btn btn-outline-info py-1 px-2" for="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_I" title="Izin">I</label>

                                                            <input type="radio" class="btn-check" 
                                                                   name="presensi[{{ $d->id_siswa }}][status]" 
                                                                   id="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_A" 
                                                                   value="Alfa" 
                                                                   {{ $curStatus === 'Alfa' ? 'checked' : '' }} autocomplete="off">
                                                            <label class="btn btn-outline-danger py-1 px-2" for="edit_st_{{ $item->presensi->id_presensi }}_{{ $d->id_siswa }}_A" title="Alfa">A</label>
                                                        </div>
                                                    </td>
                                                    <td class="py-2">
                                                        <input type="text" 
                                                               name="presensi[{{ $d->id_siswa }}][keterangan]" 
                                                               class="form-control form-control-sm py-1 px-2" 
                                                               placeholder="Keterangan..." 
                                                               value="{{ $d->keterangan ?? '' }}"
                                                               style="font-size: 0.75rem;">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="modal-footer py-2 bg-light d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan Presensi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- ===== JAVASCRIPT LOGIC UNTUK PRESENSI SISWA ===== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectJadwal = document.getElementById('schedule_id');
            const placeholder = document.getElementById('placeholderJadwal');
            const sectionPresensi = document.getElementById('sectionPresensi');
            const labelKelas = document.getElementById('labelKelasTerpilih');
            const btnSemuaHadir = document.getElementById('btnSemuaHadir');
            const searchInput = document.getElementById('searchSiswaInput');
            const rowsSiswa = document.querySelectorAll('.row-siswa');
            const emptyNotice = document.getElementById('emptyKelasSiswa');

            const countHadir = document.getElementById('countHadir');
            const countSakit = document.getElementById('countSakit');
            const countIzin  = document.getElementById('countIzin');
            const countAlfa  = document.getElementById('countAlfa');

            function updateCounts() {
                const selectedOption = selectJadwal.options[selectJadwal.selectedIndex];
                const selectedKelas = selectedOption ? selectedOption.getAttribute('data-kelas') : '';

                let h = 0, s = 0, i = 0, a = 0;
                rowsSiswa.forEach(row => {
                    const rowKelas = row.getAttribute('data-kelas');
                    if (selectedKelas && rowKelas === selectedKelas) {
                        const checked = row.querySelector('.presensi-radio:checked');
                        if (checked) {
                            if (checked.value === 'Hadir') h++;
                            else if (checked.value === 'Sakit') s++;
                            else if (checked.value === 'Izin') i++;
                            else if (checked.value === 'Alfa') a++;
                        }
                    }
                });
                countHadir.textContent = h;
                countSakit.textContent = s;
                countIzin.textContent = i;
                countAlfa.textContent = a;
            }

            function filterSiswaByKelas() {
                const selectedOption = selectJadwal.options[selectJadwal.selectedIndex];
                const selectedKelas = selectedOption ? selectedOption.getAttribute('data-kelas') : '';
                const selectedKelasNama = selectedOption ? (selectedOption.getAttribute('data-kelas-nama') || selectedKelas) : '';

                if (!selectedKelas) {
                    placeholder.classList.remove('d-none');
                    sectionPresensi.classList.add('d-none');
                    labelKelas.textContent = 'Pilih Jadwal';
                    // Sembunyikan dan nonaktifkan semua input siswa agar tidak terkirim saat submit
                    rowsSiswa.forEach(row => {
                        row.style.display = 'none';
                        row.querySelectorAll('input').forEach(input => input.disabled = true);
                    });
                    updateCounts();
                    return;
                }

                placeholder.classList.add('d-none');
                sectionPresensi.classList.remove('d-none');
                labelKelas.textContent = selectedKelasNama;

                let totalSiswaKelas = 0;
                let visibleCount = 0;
                const keyword = searchInput ? searchInput.value.toLowerCase().trim() : '';

                rowsSiswa.forEach(row => {
                    const rowKelas = row.getAttribute('data-kelas');
                    const nama = row.getAttribute('data-nama') || '';
                    const nisn = row.getAttribute('data-nisn') || '';

                    // Filter siswa STRICT: Hanya siswa yang memiliki data-kelas sama persis dengan selectedKelas
                    if (rowKelas === selectedKelas) {
                        totalSiswaKelas++;
                        // Aktifkan input siswa ini karena dia anggota kelas yang dipilih
                        row.querySelectorAll('input').forEach(input => input.disabled = false);

                        const matchSearch = !keyword || nama.includes(keyword) || nisn.includes(keyword);
                        if (matchSearch) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    } else {
                        // Siswa dari kelas lain: sembunyikan dan nonaktifkan agar tidak terkirim di POST
                        row.style.display = 'none';
                        row.querySelectorAll('input').forEach(input => input.disabled = true);
                    }
                });

                if (emptyNotice) {
                    if (totalSiswaKelas === 0) {
                        emptyNotice.classList.remove('d-none');
                        emptyNotice.innerHTML = `
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="fa-solid fa-users-slash fa-2x mb-2 text-warning d-block"></i>
                                Belum ada siswa yang terdaftar di kelas <strong>${selectedKelasNama}</strong>.<br>
                                <small class="text-muted">Waka Kurikulum dapat mengatur kelas siswa di menu <em>Manajemen Akun</em>.</small>
                            </td>`;
                    } else if (visibleCount === 0) {
                        emptyNotice.classList.remove('d-none');
                        emptyNotice.innerHTML = `
                            <td colspan="3" class="text-center text-muted py-3">
                                <i class="fa-solid fa-magnifying-glass mb-1 text-muted d-block"></i>
                                Tidak ditemukan siswa dengan kata kunci "<strong>${keyword}</strong>".
                            </td>`;
                    } else {
                        emptyNotice.classList.add('d-none');
                    }
                }

                updateCounts();
            }

            // Event saat ganti jadwal
            selectJadwal.addEventListener('change', filterSiswaByKelas);

            // Event saat tombol Semua Hadir diklik
            if (btnSemuaHadir) {
                btnSemuaHadir.addEventListener('click', function () {
                    const selectedOption = selectJadwal.options[selectJadwal.selectedIndex];
                    const selectedKelas = selectedOption ? selectedOption.getAttribute('data-kelas') : '';

                    rowsSiswa.forEach(row => {
                        const rowKelas = row.getAttribute('data-kelas');
                        if (selectedKelas && rowKelas === selectedKelas) {
                            const radioH = row.querySelector('.presensi-radio[value="Hadir"]');
                            if (radioH) {
                                radioH.checked = true;
                            }
                        }
                    });
                    updateCounts();
                });
            }

            // Event saat radio button presensi berubah
            document.querySelectorAll('.presensi-radio').forEach(radio => {
                radio.addEventListener('change', updateCounts);
            });

            // Event saat pencarian siswa diketik
            searchInput.addEventListener('input', function () {
                filterSiswaByKelas();
            });

            // Inisialisasi saat pertama kali dimuat
            filterSiswaByKelas();
        });
    </script>
@endsection
