<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\JadwalPelajaran;
use App\Models\SystemConfiguration;
use App\Models\journal;
use App\Models\Guru;

class JadwalPelajaranController extends Controller
{
    public function index()
    {
        // PENGAMAN ANTI-BOBOL: Pastikan user sudah login
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idUser = session('id_user');
        $idRole = session('id_role');
        $username = session('username');

        // Khusus Guru BK, alihkan ke panel verifikasi izin (Wali Kelas tetap memiliki hak akses jadwal & jurnal)
        if ((int) $idRole === 7) {
            return redirect()->route('kesiswaan.izin.index');
        }

        $guruLogin = null;

        $guruLogin = Guru::where('nip', $username)->first();

        // Jika akun Wali Kelas/Guru belum tercatat di tabel guru, daftarkan otomatis
        if (!$guruLogin && in_array((int) $idRole, [2, 3])) {
            $userDb = DB::table('users')->where('username', $username)->first();
            if ($userDb) {
                $idG = DB::table('guru')->insertGetId([
                    'nip' => $username,
                    'nama_guru' => $userDb->nama_lengkap ?? $username,
                ]);
                $guruLogin = Guru::find($idG);
            }
        }

        // Ambil data pendukung
        $tahunAjaran = DB::table('tahun_ajaran')->get();
        $kelas = DB::table('kelas')->get();
        $mapels = DB::table('mata_pelajaran')->get();
        $gurus = DB::table('guru')->get();
        $ruangans = DB::table('ruangan')->get();
        $config = SystemConfiguration::first();

        if ($guruLogin) {
            $jadwals = JadwalPelajaran::with(['tahunAjaran', 'kelas', 'mataPelajaran', 'guru', 'ruangan'])
                ->where('id_guru', $guruLogin->id_guru)
                ->get();

            $jurnalList = journal::with(['jadwal.kelas', 'jadwal.mataPelajaran'])
                ->whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
                ->get();
        } else {
            $jadwals = JadwalPelajaran::with(['tahunAjaran', 'kelas', 'mataPelajaran', 'guru', 'ruangan'])->get();
            $jurnalList = journal::with(['jadwal.kelas', 'jadwal.mataPelajaran'])->get();
        }

        return view('jadwal.index', compact('jadwals', 'jurnalList', 'config', 'tahunAjaran', 'kelas', 'mapels', 'gurus', 'ruangans', 'guruLogin'));
    }

    public function store(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jadwal telah dikunci oleh Waka Kurikulum.'])->withInput();
        }

        // ==========================================================
        // SAKLAR PENGECEKAN BENTROK
        // true  = dicek (jika bentrok, jadwal DITOLAK)
        // false = tidak dicek (jadwal tetap bisa disimpan walau bentrok)
        // ==========================================================
        $cekBentrokGuru = true;
        $cekBentrokRuangan = false;  // <-- false = ruangan boleh bentrok
        $cekBentrokKelas = true;

        $username = session('username');
        $guruLogin = Guru::where('nip', $username)->first();

        if (!$guruLogin && in_array((int) session('id_role'), [2, 3])) {
            $userDb = DB::table('users')->where('username', $username)->first();
            if ($userDb) {
                $idG = DB::table('guru')->insertGetId([
                    'nip' => $username,
                    'nama_guru' => $userDb->nama_lengkap ?? $username,
                ]);
                $guruLogin = Guru::find($idG);
            }
        }

        if ($guruLogin) {
            $request->merge([
                'id_guru' => $guruLogin->id_guru,
            ]);
        }

        $request->validate([
            'id_tahun_ajaran' => 'required',
            'id_kelas' => 'required',
            'id_mapel' => 'required',
            'id_guru' => 'required',
            'id_ruangan_default' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih akhir dari jam mulai!',
        ]);

        $activeCycle = $config->active_cycle ?? 'Putaran 1';
        $jamMulai = $request->jam_mulai;
        $jamSelesai = $request->jam_selesai;

        // Cek 1: Bentrok GURU
        if ($cekBentrokGuru) {
            $bentrokGuru = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'ruangan'])
                ->where('hari', $request->hari)
                ->where('cycle_type', $activeCycle)
                ->where('id_guru', $request->id_guru)
                ->where('jam_mulai', '<', $jamSelesai)
                ->where('jam_selesai', '>', $jamMulai)
                ->first();

            if ($bentrokGuru) {
                $namaKelas = $bentrokGuru->kelas ? trim(($bentrokGuru->kelas->tingkat ?? '') . ' ' . ($bentrokGuru->kelas->id_jurusan ?? $bentrokGuru->kelas->nama_kelas ?? $bentrokGuru->kelas->id_kelas)) : $bentrokGuru->id_kelas;
                $namaMapel = $bentrokGuru->mataPelajaran->nama_mapel ?? $bentrokGuru->id_mapel;
                $rentangJam = substr($bentrokGuru->jam_mulai, 0, 5) . ' - ' . substr($bentrokGuru->jam_selesai, 0, 5);
                return redirect()->back()->withErrors([
                    'error' => "BENTROK GURU! Anda sudah memiliki jadwal mengajar mapel {$namaMapel} di kelas {$namaKelas} ({$rentangJam}) pada {$request->hari} ({$activeCycle})."
                ])->withInput();
            }
        }

        // Cek 2: Bentrok RUANGAN
        if ($cekBentrokRuangan) {
            $bentrokRuangan = JadwalPelajaran::with(['guru', 'kelas', 'ruangan'])
                ->where('hari', $request->hari)
                ->where('cycle_type', $activeCycle)
                ->where('id_ruangan_default', $request->id_ruangan_default)
                ->where('jam_mulai', '<', $jamSelesai)
                ->where('jam_selesai', '>', $jamMulai)
                ->first();

            if ($bentrokRuangan) {
                $namaRuang = $bentrokRuangan->ruangan->nama_ruangan ?? $bentrokRuangan->id_ruangan_default;
                $namaGuru = $bentrokRuangan->guru->nama_guru ?? 'Guru lain';
                $namaKelas = $bentrokRuangan->kelas ? trim(($bentrokRuangan->kelas->tingkat ?? '') . ' ' . ($bentrokRuangan->kelas->id_jurusan ?? $bentrokRuangan->kelas->nama_kelas ?? $bentrokRuangan->kelas->id_kelas)) : $bentrokRuangan->id_kelas;
                $rentangJam = substr($bentrokRuangan->jam_mulai, 0, 5) . ' - ' . substr($bentrokRuangan->jam_selesai, 0, 5);
                return redirect()->back()->withErrors([
                    'error' => "BENTROK RUANGAN! Ruangan {$namaRuang} sedang digunakan oleh {$namaGuru} untuk kelas {$namaKelas} ({$rentangJam}) pada {$request->hari} ({$activeCycle})."
                ])->withInput();
            }
        }

        // Cek 3: Bentrok KELAS
        if ($cekBentrokKelas) {
            $bentrokKelas = JadwalPelajaran::with(['guru', 'mataPelajaran', 'kelas'])
                ->where('hari', $request->hari)
                ->where('cycle_type', $activeCycle)
                ->where('id_kelas', $request->id_kelas)
                ->where('jam_mulai', '<', $jamSelesai)
                ->where('jam_selesai', '>', $jamMulai)
                ->first();

            if ($bentrokKelas) {
                $namaKelas = $bentrokKelas->kelas ? trim(($bentrokKelas->kelas->tingkat ?? '') . ' ' . ($bentrokKelas->kelas->id_jurusan ?? $bentrokKelas->kelas->nama_kelas ?? $bentrokKelas->kelas->id_kelas)) : $bentrokKelas->id_kelas;
                $namaMapel = $bentrokKelas->mataPelajaran->nama_mapel ?? $bentrokKelas->id_mapel;
                $namaGuru = $bentrokKelas->guru->nama_guru ?? 'Guru lain';
                $rentangJam = substr($bentrokKelas->jam_mulai, 0, 5) . ' - ' . substr($bentrokKelas->jam_selesai, 0, 5);
                return redirect()->back()->withErrors([
                    'error' => "BENTROK KELAS! Kelas {$namaKelas} sudah memiliki jadwal pelajaran {$namaMapel} bersama {$namaGuru} ({$rentangJam}) pada {$request->hari} ({$activeCycle})."
                ])->withInput();
            }
        }

        JadwalPelajaran::create(array_merge($request->all(), [
            'cycle_type' => $activeCycle
        ]));

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    public function updateConfig(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengubah konfigurasi jadwal.']);
        }

        $config = SystemConfiguration::first();
        if ($config) {
            $config->update([
                'active_cycle' => $request->active_cycle,
                'is_schedule_locked' => $request->is_schedule_locked,
            ]);
        }

        return redirect()->back()->with('success', 'Konfigurasi berhasil diperbarui!');
    }

    // ==========================================
    // FITUR MANAJEMEN AKUN & ROLE (KHUSUS WAKA)
    // ==========================================

    public function indexAkun()
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengelola akun.']);
        }

        // Auto-Sinkronisasi: Jika ada akun bertipe Siswa di users tapi belum ada di tabel siswa, masukkan otomatis
        $siswaUsers = DB::table('users')->whereIn('id_role', [1, 5])->get();
        foreach ($siswaUsers as $su) {
            $cek = DB::table('siswa')->where('nisn', $su->username)->exists();
            if (!$cek) {
                DB::table('siswa')->insert([
                    'nisn' => $su->username,
                    'nama_siswa' => $su->nama_lengkap,
                    'status_utama' => 'Aktif',
                ]);
            }
        }

        // Auto-Sinkronisasi: Jika ada akun bertipe Guru di users tapi belum ada di tabel guru, masukkan otomatis
        $guruUsers = DB::table('users')->whereIn('id_role', [2, 3, 7])->get();
        foreach ($guruUsers as $gu) {
            $cek = DB::table('guru')->where('nip', $gu->username)->exists();
            if (!$cek) {
                DB::table('guru')->insert([
                    'nip' => $gu->username,
                    'nama_guru' => $gu->nama_lengkap,
                ]);
            }
        }

        // Auto-Sinkronisasi: Pastikan kolom id_kelas ada di tabel users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'id_kelas')) {
            try {
                Schema::table('users', function ($table) {
                    $table->string('id_kelas', 20)->nullable()->after('id_role');
                });
            } catch (\Throwable $e) {
            }
        }

        // Auto-Sinkronisasi: Pastikan kolom email ada di tabel users (diperlukan untuk OTP lupa password)
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'email')) {
            try {
                Schema::table('users', function ($table) {
                    $table->string('email', 150)->nullable()->after('no_hp');
                });
            } catch (\Throwable $e) {
            }
        }

        $users = DB::table('users')->get();
        $kelas = DB::table('kelas')->get();
        // Urutkan kelas secara logis (berdasarkan tingkat dan id_jurusan)
        $kelas = $kelas->sortBy(function ($k) {
            return [
                $k->tingkat ?? '',
                $k->id_jurusan ?? $k->nama_kelas ?? '',
                (int) $k->id_kelas,
            ];
        })->values();
        $siswaMap = DB::table('siswa')->get()->keyBy('nisn');

        return view('jadwal.manajemen_akun', compact('users', 'kelas', 'siswaMap'));
    }

    public function storeAkun(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $request->validate([
            'username' => 'required|unique:users,username',
            'nama_lengkap' => 'required',
            'no_hp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100|unique:users,email',
            'password' => 'required|min:5',
            'id_role' => 'required',
            'id_kelas' => 'nullable|string',
            'id_kelas_wali' => 'nullable|string',
        ]);

        // 1. Masukkan data ke tabel users terlebih dahulu
        $userData = [
            'username' => $request->username,
            'password_hash' => bcrypt($request->password),
            'nama_lengkap' => $request->nama_lengkap,
            'no_hp' => $request->filled('no_hp') ? trim($request->no_hp) : null,
            'email' => $request->filled('email') ? strtolower(trim($request->email)) : null,
            'id_role' => $request->id_role,
        ];
        if (Schema::hasColumn('users', 'id_kelas')) {
            $waliK = $request->input('id_kelas_wali') ?: $request->input('id_kelas');
            $userData['id_kelas'] = ((int) $request->id_role === 3 && !empty($waliK)) ? $waliK : null;
        }
        DB::table('users')->insert($userData);

        // 2. JIKA role yang dipilih adalah GURU, WALI KELAS, atau BK (id_role == 2, 3, 7), 
        // masukkan otomatis datanya ke dalam tabel 'guru' jika belum ada
        if (in_array((int) $request->id_role, [2, 3, 7])) {
            $cekGuru = DB::table('guru')->where('nip', $request->username)->exists();
            if (!$cekGuru) {
                DB::table('guru')->insert([
                    'nip' => $request->username,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            }
        }

        // 3. JIKA role yang dipilih adalah SISWA (id_role == 5 atau 1),
        // masukkan otomatis datanya ke dalam tabel 'siswa' jika belum ada
        if (in_array((int) $request->id_role, [1, 5])) {
            $cekSiswa = DB::table('siswa')->where('nisn', $request->username)->exists();
            if (!$cekSiswa) {
                $dataSiswa = [
                    'nisn' => $request->username,
                    'nama_siswa' => $request->nama_lengkap,
                    'status_utama' => 'Aktif',
                ];
                if (Schema::hasColumn('siswa', 'id_kelas') && $request->filled('id_kelas')) {
                    $dataSiswa['id_kelas'] = $request->id_kelas;
                }
                DB::table('siswa')->insert($dataSiswa);
            }
        }

        $labelRole = match ((int) $request->id_role) {
            7 => 'Guru BK',
            3 => 'Wali Kelas',
            2 => 'Guru Pengampu',
            1, 5 => 'Siswa',
            4 => 'Waka Kurikulum',
            default => 'Pengguna',
        };

        return redirect()->route('akun.index')->with('success', "Akun baru berhasil dibuat sebagai {$labelRole}!");
    }

    public function updateRole(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $request->validate([
            'id_user' => 'required',
            'id_role' => 'required|in:2,3,4,5,7',
            'id_kelas' => 'nullable|string',
            'no_hp' => 'nullable|string|max:20',
        ]);

        $user = DB::table('users')->where('id_user', $request->id_user)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'User tidak ditemukan!']);
        }

        $updateData = ['id_role' => $request->id_role];
        if ($request->has('no_hp')) {
            $updateData['no_hp'] = $request->filled('no_hp') ? trim($request->no_hp) : null;
        }

        // Simpan / update id_kelas untuk wali kelas (id_role 3)
        if (Schema::hasColumn('users', 'id_kelas')) {
            if ((int) $request->id_role === 3) {
                $updateData['id_kelas'] = $request->filled('id_kelas') ? $request->id_kelas : null;
            } else {
                $updateData['id_kelas'] = null;
            }
        }

        DB::table('users')->where('id_user', $request->id_user)->update($updateData);

        // Jika role yang dipilih adalah Guru, Wali Kelas, atau Guru BK, pastikan data ada di tabel guru
        if (in_array((int) $request->id_role, [2, 3, 7])) {
            $cekGuru = DB::table('guru')->where('nip', $user->username)->exists();
            if (!$cekGuru) {
                DB::table('guru')->insert([
                    'nip' => $user->username,
                    'nama_guru' => $user->nama_lengkap,
                ]);
            }
        }

        // Jika role yang dipilih adalah Siswa, pastikan data ada di tabel siswa
        if (in_array((int) $request->id_role, [1, 5])) {
            $cekSiswa = DB::table('siswa')->where('nisn', $user->username)->exists();
            if (!$cekSiswa) {
                DB::table('siswa')->insert([
                    'nisn' => $user->username,
                    'nama_siswa' => $user->nama_lengkap,
                    'status_utama' => 'Aktif',
                ]);
            }
        }

        $roleLabels = [
            4 => 'Waka Kurikulum',
            3 => 'Wali Kelas',
            2 => 'Guru Pengampu',
            7 => 'Guru BK',
            5 => 'Siswa',
        ];
        $newLabel = $roleLabels[(int) $request->id_role] ?? 'Role';

        return redirect()->back()->with('success', "Hak akses akun {$user->username} berhasil diubah menjadi {$newLabel}!");
    }

    public function updateKelasSiswa(Request $request)
    {
        if (!session()->has('id_user') || session('id_role') != 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $request->validate([
            'id_siswa' => 'required',
            'id_kelas' => 'nullable|string',
        ]);

        DB::table('siswa')->where('id_siswa', $request->id_siswa)->update([
            'id_kelas' => $request->id_kelas ?: null,
        ]);

        return redirect()->back()->with('success', 'Kelas siswa berhasil diperbarui!');
    }

    public function destroyAkun($id)
    {
        if (!session()->has('id_user') || (int) session('id_role') !== 4) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        $user = DB::table('users')->where('id_user', $id)->first();
        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'Akun tidak ditemukan!']);
        }

        // Cegah menghapus akun sendiri yang sedang login
        if ((int) session('id_user') === (int) $user->id_user) {
            return redirect()->back()->withErrors(['error' => 'Anda tidak dapat menghapus akun yang sedang digunakan!']);
        }

        DB::beginTransaction();
        try {
            // Jika akun adalah siswa (role 1 atau 5), hapus juga dari tabel siswa jika ada
            if (in_array((int) $user->id_role, [1, 5])) {
                DB::table('siswa')->where('nisn', $user->username)->delete();
            }

            // Hapus user
            DB::table('users')->where('id_user', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', "Akun {$user->username} ({$user->nama_lengkap}) berhasil dihapus!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus akun: ' . $e->getMessage()]);
        }
    }
}