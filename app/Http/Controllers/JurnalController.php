<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\LeaveRequest;
use App\Models\PresensiHeader;
use App\Models\PresensiDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class JurnalController extends Controller
{
    /**
     * Toleransi (menit) pengisian jurnal setelah jam selesai pelajaran.
     * Jurnal hanya bisa diisi sejak jam mulai pelajaran sampai jam selesai + toleransi ini.
     * Nilainya diambil dari App\Support\JurnalTerlambat::TOLERANSI_MENIT agar notifikasi dashboard selalu sinkron.
     */
    private const TOLERANSI_MENIT = \App\Support\JurnalTerlambat::TOLERANSI_MENIT;

    /**
     * Status waktu pengisian jurnal untuk sebuah jadwal pada hari ini:
     * 'belum_mulai' | 'boleh' | 'lewat'.
     */
    private function statusWaktu(JadwalPelajaran $j): string
    {
        $now = now();
        $today = $now->toDateString();
        $mulai = \Carbon\Carbon::parse($today . ' ' . $j->jam_mulai);
        $batas = \Carbon\Carbon::parse($today . ' ' . $j->jam_selesai)->addMinutes(self::TOLERANSI_MENIT);

        if ($now->lt($mulai)) return 'belum_mulai';
        if ($now->gt($batas)) return 'lewat';
        return 'boleh';
    }

    /**
     * Nama hari ini (Senin..Sabtu) mengikuti zona waktu config/app.php (Asia/Jakarta).
     * Mengembalikan null pada hari Minggu (tidak ada jadwal).
     */
    private function hariIni(): ?string
    {
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        return $namaHari[now()->dayOfWeekIso] ?? null;
    }

    public function index()
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        // Akses untuk Guru Pengampu (2) dan Wali Kelas (3). Guru BK (7) hanya berwenang memverifikasi izin
        if (in_array((int) session('id_role'), [7])) {
            return redirect()->route('kesiswaan.izin.index')->withErrors(['error' => 'Akses ditolak! Guru BK hanya berwenang memverifikasi izin siswa.']);
        }

        $user = Auth::user();
        $username = $user ? $user->username : session('username');

        $guru = Guru::where('nip', $username)->first();

        // Jika akun Guru / Wali Kelas belum ada di tabel guru, sinkronkan otomatis
        if (!$guru) {
            $userDb = DB::table('users')->where('username', $username)->first();
            if ($userDb) {
                $idG = DB::table('guru')->insertGetId([
                    'nip' => $username,
                    'nama_guru' => $userDb->nama_lengkap ?? $username,
                ]);
                $guru = Guru::find($idG);
            }
        }

        if (!$guru) {
            return redirect()->back()->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'tahunAjaran', 'ruangan'])
            ->where('id_guru', $guru->id_guru)
            ->get();

        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran'])
            ->whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->get();

        // Muat rekap presensi untuk setiap jurnal jika tabel presensi tersedia
        if (Schema::hasTable('presensi_header') && Schema::hasTable('presensi_detail')) {
            // Pembersihan otomatis: Hapus data presensi_detail lama yang tersimpan sebelum perbaikan filter kelas
            if (Schema::hasColumn('siswa', 'id_kelas')) {
                try {
                    $invalidDetailIds = DB::table('presensi_detail')
                        ->join('presensi_header', 'presensi_detail.id_presensi', '=', 'presensi_header.id_presensi')
                        ->join('jadwal_pelajaran', 'presensi_header.id_jadwal', '=', 'jadwal_pelajaran.id_jadwal')
                        ->join('siswa', 'presensi_detail.id_siswa', '=', 'siswa.id_siswa')
                        ->whereNotNull('jadwal_pelajaran.id_kelas')
                        ->where('jadwal_pelajaran.id_kelas', '!=', '')
                        ->where(function ($query) {
                            $query->whereColumn('siswa.id_kelas', '!=', 'jadwal_pelajaran.id_kelas')
                                ->orWhereNull('siswa.id_kelas');
                        })
                        ->pluck('presensi_detail.id_detail');

                    if ($invalidDetailIds->isNotEmpty()) {
                        DB::table('presensi_detail')->whereIn('id_detail', $invalidDetailIds)->delete();
                    }

                    if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'id')) {
                        $siswaCol = Schema::hasColumn('attendances', 'id_siswa') ? 'id_siswa' : (Schema::hasColumn('attendances', 'student_id') ? 'student_id' : null);
                        $schCol = Schema::hasColumn('attendances', 'schedule_id') ? 'schedule_id' : (Schema::hasColumn('attendances', 'id_jadwal') ? 'id_jadwal' : null);

                        if ($siswaCol && $schCol) {
                            $invalidAttIds = DB::table('attendances')
                                ->join('jadwal_pelajaran', "attendances.{$schCol}", '=', 'jadwal_pelajaran.id_jadwal')
                                ->join('siswa', "attendances.{$siswaCol}", '=', 'siswa.id_siswa')
                                ->whereNotNull('jadwal_pelajaran.id_kelas')
                                ->where('jadwal_pelajaran.id_kelas', '!=', '')
                                ->where(function ($query) {
                                    $query->whereColumn('siswa.id_kelas', '!=', 'jadwal_pelajaran.id_kelas')
                                        ->orWhereNull('siswa.id_kelas');
                                })
                                ->pluck('attendances.id');

                            if ($invalidAttIds->isNotEmpty()) {
                                DB::table('attendances')->whereIn('id', $invalidAttIds)->delete();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Abaikan jika terjadi kendala saat pembersihan otomatis
                }
            }

            $scheduleIds = $jadwals->pluck('id_jadwal');
            $presensiHeaders = PresensiHeader::with(['details.siswa'])
                ->whereIn('id_jadwal', $scheduleIds)
                ->get();

            foreach ($jurnalList as $j) {
                $ph = $presensiHeaders->first(function ($ph) use ($j) {
                    return $ph->id_jadwal == $j->schedule_id && $ph->tanggal == $j->date;
                });

                if ($ph && $j->jadwal && !empty($j->jadwal->id_kelas) && Schema::hasColumn('siswa', 'id_kelas')) {
                    // Filter detail presensi agar HANYA menampilkan siswa yang benar-benar terdaftar di kelas jadwal ini
                    $filteredDetails = $ph->details->filter(function ($d) use ($j) {
                        return $d->siswa && (string) $d->siswa->id_kelas === (string) $j->jadwal->id_kelas;
                    });
                    $ph->setRelation('details', $filteredDetails);
                }

                $j->presensi = $ph;
            }
        }

        // Ambil data siswa aktif untuk form presensi
        $hasKelasCol = Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'id_kelas');
        $siswaList = Siswa::with('kelas')
            ->where('status_utama', 'Aktif')
            ->orderBy('nama_siswa')
            ->get();

        // Hanya jadwal yang jatuh pada HARI INI yang boleh dipilih saat mengisi jurnal
        $hariIni = $this->hariIni();
        $jadwalHariIni = $hariIni
            ? $jadwals->filter(fn($j) => $j->hari === $hariIni)->values()
            : collect();

        // Jadwal hari ini yang jurnalnya sudah diisi (tidak boleh diisi dua kali)
        $sudahIsiIds = Journal::whereIn('schedule_id', $jadwalHariIni->pluck('id_jadwal'))
            ->whereDate('date', now()->toDateString())
            ->pluck('schedule_id')
            ->map(fn($id) => (string) $id)
            ->all();
        $jadwalHariIni = $jadwalHariIni->reject(fn($j) => in_array((string) $j->id_jadwal, $sudahIsiIds, true))->values();

        // Status jendela waktu pengisian per jadwal (belum_mulai | boleh | lewat)
        $statusWaktuMap = $jadwalHariIni->mapWithKeys(fn($j) => [$j->id_jadwal => $this->statusWaktu($j)])->all();
        $toleransiMenit = self::TOLERANSI_MENIT;
        $jumlahSudahIsi = count($sudahIsiIds);

        // Ambil pengajuan izin/sakit siswa yang disetujui untuk hari ini
        $today = now()->toDateString();
        $todayLeaves = collect();
        if (Schema::hasTable('leave_requests')) {
            $todayLeaves = LeaveRequest::where('status_approval', 'Approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->get()
                ->keyBy('student_id');
        }

        return view('jurnal.index', compact('jadwals', 'jadwalHariIni', 'hariIni', 'today', 'jurnalList', 'guru', 'siswaList', 'todayLeaves', 'hasKelasCol', 'statusWaktuMap', 'toleransiMenit', 'jumlahSudahIsi'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        // Hanya Guru BK (7) yang tidak bisa isi jurnal - Wali Kelas (3) BOLEH isi jurnal
        if ((int) session('id_role') === 7) {
            return redirect()->route('kesiswaan.izin.index')->withErrors(['error' => 'Akses ditolak! Guru BK hanya berwenang memverifikasi izin siswa.']);
        }

        $request->validate([
            'schedule_id' => 'required',
            'topic_description' => 'required',
            'photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            // Foto wajib berasal dari kamera real-time (form menandai photo_source=camera)
            'photo_source' => 'required|in:camera',
            'presensi' => 'nullable|array',
        ], [
            'photo.required' => 'Foto bukti KBM wajib diambil langsung dari kamera.',
            'photo_source.required' => 'Foto bukti KBM wajib diambil langsung dari kamera (unggah dari galeri/berkas tidak diizinkan).',
            'photo_source.in' => 'Foto bukti KBM wajib diambil langsung dari kamera (unggah dari galeri/berkas tidak diizinkan).',
        ]);

        $jadwal = JadwalPelajaran::findOrFail($request->schedule_id);

        // Jurnal hanya boleh diisi untuk jadwal yang jatuh pada hari ini
        $hariIni = $this->hariIni();
        if (!$hariIni || $jadwal->hari !== $hariIni) {
            return redirect()->back()
                ->withErrors(['error' => 'Jurnal hanya dapat diisi untuk jadwal hari ini (' . ($hariIni ?? 'Minggu') . '). Jadwal ' . $jadwal->hari . ' tidak dapat diisi sekarang.'])
                ->withInput();
        }

        // Jurnal hanya boleh diisi pada jam pelajaran (jam mulai s.d. jam selesai + toleransi)
        $statusWaktu = $this->statusWaktu($jadwal);
        $jamLabel = substr($jadwal->jam_mulai, 0, 5) . '–' . substr($jadwal->jam_selesai, 0, 5);
        if ($statusWaktu === 'belum_mulai') {
            return redirect()->back()
                ->withErrors(['error' => 'Pelajaran ini belum dimulai (jadwal ' . $jamLabel . '). Jurnal baru dapat diisi mulai pukul ' . substr($jadwal->jam_mulai, 0, 5) . '.'])
                ->withInput();
        }
        if ($statusWaktu === 'lewat') {
            return redirect()->back()
                ->withErrors(['error' => 'Batas waktu pengisian jurnal sudah lewat (jadwal ' . $jamLabel . ', toleransi ' . self::TOLERANSI_MENIT . ' menit setelah jam selesai).'])
                ->withInput();
        }

        // Cegah jurnal ganda untuk jadwal yang sama pada hari yang sama
        $sudahAda = Journal::where('schedule_id', $jadwal->id_jadwal)
            ->whereDate('date', now()->toDateString())
            ->exists();
        if ($sudahAda) {
            return redirect()->back()
                ->withErrors(['error' => 'Jurnal untuk jadwal ini sudah diisi hari ini. Gunakan fitur edit presensi jika ada yang perlu diubah.']);
        }

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            // Simpan ke storage/app/public/uploads/jurnal agar tidak trigger auto-reload artisan serve
            Storage::disk('public')->putFileAs('uploads/jurnal', $file, $filename);
            $photoPath = 'storage/uploads/jurnal/' . $filename;
        }

        DB::beginTransaction();
        try {
            $today = now()->toDateString();

            // 1. Simpan Jurnal Utama Guru
            $journalId = DB::table('journals')->insertGetId([
                'schedule_id' => $jadwal->id_jadwal,
                'date' => $today,
                'topic_description' => $request->topic_description,
                'photo_path' => $photoPath,
            ]);

            // Ambil daftar id_siswa yang terdaftar pada kelas jadwal ini (jika kolom id_kelas ada)
            $hasKelasCol = Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'id_kelas');
            $validSiswaIds = $hasKelasCol
                ? DB::table('siswa')->where('id_kelas', $jadwal->id_kelas)->pluck('id_siswa')->map(fn($id) => (string) $id)->toArray()
                : null;

            // 2. Simpan Presensi Header & Detail Siswa (Standar UKK SCP)
            if (Schema::hasTable('presensi_header')) {
                $idRuangan = $jadwal->id_ruangan_default ?: (DB::table('ruangan')->value('id_ruangan') ?? 'R1');

                $idPresensi = DB::table('presensi_header')->insertGetId([
                    'id_jadwal' => $jadwal->id_jadwal,
                    'tanggal' => $today,
                    'id_ruangan_digunakan' => $idRuangan,
                    'id_guru_pengajar' => $jadwal->id_guru,
                    'catatan_kbm' => $request->topic_description,
                ]);

                if (Schema::hasTable('presensi_detail') && !empty($request->presensi) && is_array($request->presensi)) {
                    foreach ($request->presensi as $idSiswa => $pData) {
                        // Pastikan hanya siswa dari kelas yang diajar pada jadwal ini yang disimpan
                        if ($validSiswaIds !== null && !in_array((string) $idSiswa, $validSiswaIds)) {
                            continue;
                        }

                        $status = in_array($pData['status'] ?? '', ['Hadir', 'Sakit', 'Izin', 'Alfa'])
                            ? $pData['status']
                            : 'Hadir';

                        DB::table('presensi_detail')->insert([
                            'id_presensi' => $idPresensi,
                            'id_siswa' => $idSiswa,
                            'status_kehadiran' => $status,
                            'keterangan' => !empty($pData['keterangan']) ? trim($pData['keterangan']) : null,
                        ]);
                    }
                }
            }

            // 3. Simpan juga ke tabel attendances jika ada di database
            if (Schema::hasTable('attendances') && !empty($request->presensi) && is_array($request->presensi)) {
                $cols = Schema::getColumnListing('attendances');
                foreach ($request->presensi as $idSiswa => $pData) {
                    // Pastikan hanya siswa dari kelas yang diajar pada jadwal ini yang disimpan
                    if ($validSiswaIds !== null && !in_array((string) $idSiswa, $validSiswaIds)) {
                        continue;
                    }

                    $status = in_array($pData['status'] ?? '', ['Hadir', 'Sakit', 'Izin', 'Alfa'])
                        ? $pData['status']
                        : 'Hadir';

                    $row = [];
                    if (in_array('journal_id', $cols))
                        $row['journal_id'] = $journalId;
                    if (in_array('schedule_id', $cols))
                        $row['schedule_id'] = $jadwal->id_jadwal;
                    if (in_array('id_siswa', $cols))
                        $row['id_siswa'] = $idSiswa;
                    if (in_array('student_id', $cols))
                        $row['student_id'] = $idSiswa;
                    if (in_array('status_kehadiran', $cols))
                        $row['status_kehadiran'] = $status;
                    if (in_array('status', $cols))
                        $row['status'] = $status;
                    if (in_array('keterangan', $cols))
                        $row['keterangan'] = !empty($pData['keterangan']) ? trim($pData['keterangan']) : null;
                    if (in_array('date', $cols))
                        $row['date'] = $today;
                    if (in_array('tanggal', $cols))
                        $row['tanggal'] = $today;

                    if (!empty($row)) {
                        DB::table('attendances')->insert($row);
                    }
                }
            }

            DB::commit();
            return redirect()->route('jurnal.index')->with('success', 'Jurnal mengajar KBM berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Update/Edit Presensi Siswa dari Jurnal yang telah diisi sebelumnya
     */
    public function updatePresensi(Request $request)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        if (in_array((int) session('id_role'), [7])) {
            return redirect()->route('kesiswaan.izin.index')->withErrors(['error' => 'Akses ditolak!']);
        }

        $request->validate([
            'id_presensi' => 'required',
            'presensi' => 'required|array',
            'catatan_kbm' => 'nullable|string',
        ]);

        $header = DB::table('presensi_header')->where('id_presensi', $request->id_presensi)->first();
        if (!$header) {
            return redirect()->back()->withErrors(['error' => 'Data presensi tidak ditemukan!']);
        }

        // Presensi / jurnal hanya dapat diedit pada hari yang sama dengan tanggal jurnal
        if (\Carbon\Carbon::parse($header->tanggal)->toDateString() !== now()->toDateString()) {
            return redirect()->back()->withErrors(['error' => 'Presensi tidak dapat diedit. Jurnal hanya bisa diedit pada hari yang sama dengan tanggal pengisian.']);
        }

        DB::beginTransaction();
        try {
            // Update catatan KBM bila ada perubahan
            if ($request->has('catatan_kbm')) {
                DB::table('presensi_header')->where('id_presensi', $request->id_presensi)->update([
                    'catatan_kbm' => $request->catatan_kbm,
                ]);

                // Update juga catatan topik di tabel journals bila tanggal dan jadwal cocok
                DB::table('journals')
                    ->where('schedule_id', $header->id_jadwal)
                    ->where('date', $header->tanggal)
                    ->update([
                        'topic_description' => $request->catatan_kbm,
                    ]);
            }

            // Update status kehadiran masing-masing siswa
            foreach ($request->presensi as $idSiswa => $pData) {
                $status = in_array($pData['status'] ?? '', ['Hadir', 'Sakit', 'Izin', 'Alfa'])
                    ? $pData['status']
                    : 'Hadir';
                $ket = !empty($pData['keterangan']) ? trim($pData['keterangan']) : null;

                $detailExists = DB::table('presensi_detail')
                    ->where('id_presensi', $request->id_presensi)
                    ->where('id_siswa', $idSiswa)
                    ->exists();

                if ($detailExists) {
                    DB::table('presensi_detail')
                        ->where('id_presensi', $request->id_presensi)
                        ->where('id_siswa', $idSiswa)
                        ->update([
                            'status_kehadiran' => $status,
                            'keterangan' => $ket,
                        ]);
                } else {
                    DB::table('presensi_detail')->insert([
                        'id_presensi' => $request->id_presensi,
                        'id_siswa' => $idSiswa,
                        'status_kehadiran' => $status,
                        'keterangan' => $ket,
                    ]);
                }

                // Update juga tabel attendances jika ada
                if (Schema::hasTable('attendances')) {
                    $cols = Schema::getColumnListing('attendances');
                    $siswaCol = in_array('id_siswa', $cols) ? 'id_siswa' : (in_array('student_id', $cols) ? 'student_id' : null);
                    $schCol = in_array('schedule_id', $cols) ? 'schedule_id' : (in_array('id_jadwal', $cols) ? 'id_jadwal' : null);

                    if ($siswaCol && $schCol) {
                        $attQuery = DB::table('attendances')
                            ->where($schCol, $header->id_jadwal)
                            ->where($siswaCol, $idSiswa);

                        if (in_array('date', $cols)) {
                            $attQuery->where('date', $header->tanggal);
                        } elseif (in_array('tanggal', $cols)) {
                            $attQuery->where('tanggal', $header->tanggal);
                        }

                        $attUpdate = [];
                        if (in_array('status_kehadiran', $cols)) $attUpdate['status_kehadiran'] = $status;
                        if (in_array('status', $cols)) $attUpdate['status'] = $status;
                        if (in_array('keterangan', $cols)) $attUpdate['keterangan'] = $ket;

                        if (!empty($attUpdate)) {
                            $attQuery->update($attUpdate);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Data presensi siswa berhasil diperbarui!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal memperbarui presensi: ' . $e->getMessage()]);
        }
    }

    /**
     * Pengingat jurnal: dipanggil berkala oleh layout (JSON).
     * Mengembalikan jadwal hari ini milik guru yang akan selesai dalam
     * <= 15 menit tetapi jurnalnya belum diisi.
     */
    public function pengingat()
    {
        $kosong = response()->json(['reminders' => []]);

        // Guru Pengampu (role 2) dan Wali Kelas (role 3) bisa mendapat pengingat jurnal
        if (!session()->has('id_user') || !in_array((int) session('id_role'), [2, 3])) {
            return $kosong;
        }

        $guru = Guru::where('nip', session('username'))->first();
        if (!$guru) {
            return $kosong;
        }

        $now = now(); // zona waktu mengikuti config/app.php (Asia/Jakarta)
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $hari = $namaHari[$now->dayOfWeekIso] ?? null;
        if (!$hari) {
            return $kosong; // Minggu tidak ada jadwal
        }

        // active_cycle bisa tersimpan sebagai "1" atau "Putaran 1" -> samakan jadi angka
        $config = SystemConfiguration::first();
        $normalize = fn($v) => preg_replace('/\D/', '', (string) $v) ?: '1';
        $activeCycle = $normalize($config->active_cycle ?? '1');

        $jadwals = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where('id_guru', $guru->id_guru)
            ->where('hari', $hari)
            ->whereNotNull('jam_selesai')
            ->get()
            ->filter(fn($j) => $normalize($j->cycle_type) === $activeCycle);

        if ($jadwals->isEmpty()) {
            return $kosong;
        }

        $today = $now->toDateString();
        $sudahIsi = Journal::whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->whereDate('date', $today)
            ->pluck('schedule_id')
            ->map(fn($id) => (string) $id)
            ->all();

        $reminders = [];
        foreach ($jadwals as $j) {
            $selesai = \Carbon\Carbon::parse($today . ' ' . $j->jam_selesai);
            $mulaiPengingat = $selesai->copy()->subMinutes(15);

            if ($now->lt($mulaiPengingat) || $now->gte($selesai)) {
                continue;
            }
            if (in_array((string) $j->id_jadwal, $sudahIsi, true)) {
                continue;
            }

            $reminders[] = [
                'id' => $j->id_jadwal,
                'key' => $j->id_jadwal . '_' . $today,
                'mapel' => $j->mataPelajaran->nama_mapel ?? $j->id_mapel,
                'kelas' => $j->kelas->nama_kelas ?? $j->id_kelas,
                'selesai' => substr($j->jam_selesai, 0, 5),
                'sisa_menit' => max(1, (int) ceil(($selesai->timestamp - $now->timestamp) / 60)),
            ];
        }

        return response()->json(['reminders' => $reminders]);
    }

    /**
     * Fitur Rekap Jurnal untuk Waka Kurikulum
     */
    public function rekapWaka()
    {
        // Pastikan yang mengakses adalah Waka Kurikulum (id_role == 4)
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        // Ambil semua data jurnal beserta relasi jadwal, kelas, mata pelajaran, dan data gurunya
        $jurnalList = Journal::with(['jadwal.kelas.jurusan', 'jadwal.mataPelajaran', 'jadwal.guru'])
            ->orderBy('date', 'desc')
            ->get();

        if (Schema::hasTable('presensi_header') && Schema::hasTable('presensi_detail')) {
            $presensiHeaders = PresensiHeader::with(['details.siswa'])->get();
            foreach ($jurnalList as $j) {
                $ph = $presensiHeaders->first(function ($ph) use ($j) {
                    return $ph->id_jadwal == $j->schedule_id && $ph->tanggal == $j->date;
                });

                if ($ph && $j->jadwal && !empty($j->jadwal->id_kelas) && Schema::hasColumn('siswa', 'id_kelas')) {
                    $filteredDetails = $ph->details->filter(function ($d) use ($j) {
                        return $d->siswa && (string) $d->siswa->id_kelas === (string) $j->jadwal->id_kelas;
                    });
                    $ph->setRelation('details', $filteredDetails);
                }

                $j->presensi = $ph;
            }
        }

        return view('jurnal.rekap_waka', compact('jurnalList'));
    }
}