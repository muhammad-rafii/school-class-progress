<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KesiswaanController extends Controller
{
    public function index()
    {
        // Pengaman Hak Akses: Khusus Tim Kesiswaan (Wali Kelas id_role 3 & Guru BK id_role 7) serta Waka Kurikulum (4)
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idRole = (int) session('id_role');
        $idUser = session('id_user');

        if (!in_array($idRole, [3, 7, 4])) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Menu ini khusus untuk Wali Kelas dan Guru BK.']);
        }

        $this->pastikanKolomCatatan();

        $query = LeaveRequest::with(['student.kelas', 'verifier'])->orderBy('id', 'desc');

        // Jika user adalah Wali Kelas (id_role == 3), batasi hanya menampilkan surat izin siswa dari kelasnya
        $waliKelasInfo = null;
        if ($idRole === 3) {
            $user = DB::table('users')->where('id_user', $idUser)->first();
            $idKelasWali = $user->id_kelas ?? null;
            $waliKelasInfo = $idKelasWali;

            if (!empty($idKelasWali)) {
                $query->whereHas('student', function ($q) use ($idKelasWali) {
                    $q->where('id_kelas', $idKelasWali);
                });
            } else {
                // Wali Kelas belum diatur kelasnya oleh Waka Kurikulum
                $query->whereRaw('1 = 0');
            }
        }

        $pendingLeaves = $query->get();

        return view('kesiswaan.izin.index', compact('pendingLeaves', 'waliKelasInfo'));
    }

    /**
     * Pastikan kolom catatan_verifikasi ada di tabel leave_requests.
     * Jika belum ada, dibuat otomatis (tidak perlu menjalankan SQL / migrate manual).
     */
    private function pastikanKolomCatatan(): bool
    {
        if (!Schema::hasTable('leave_requests')) {
            return false;
        }

        if (!Schema::hasColumn('leave_requests', 'catatan_verifikasi')) {
            try {
                Schema::table('leave_requests', function ($table) {
                    $table->text('catatan_verifikasi')->nullable();
                });
            } catch (\Throwable $e) {
                return false;
            }
        }

        return Schema::hasColumn('leave_requests', 'catatan_verifikasi');
    }

    public function updateStatus(Request $request, $id)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $idRole = (int) session('id_role');
        $idUser = session('id_user');

        if (!in_array($idRole, [3, 7, 4])) {
            return redirect()->back()->withErrors(['error' => 'Akses ditolak! Anda tidak memiliki wewenang untuk memverifikasi izin.']);
        }

        $request->validate([
            'status_approval' => 'required|in:Approved,Rejected',
            // Catatan WAJIB diisi jika izin ditolak
            'catatan_verifikasi' => 'required_if:status_approval,Rejected|nullable|string|max:500',
        ], [
            'catatan_verifikasi.required_if' => 'Catatan penolakan wajib diisi agar siswa tahu alasan izinnya ditolak.',
            'catatan_verifikasi.max' => 'Catatan maksimal 500 karakter.',
        ]);

        $leave = LeaveRequest::with('student')->findOrFail($id);

        // Jika Wali Kelas, pastikan siswa tersebut memang berada di kelas yang diampu
        if ($idRole === 3) {
            $user = DB::table('users')->where('id_user', $idUser)->first();
            $idKelasWali = $user->id_kelas ?? null;

            if (empty($idKelasWali) || !$leave->student || (string) $leave->student->id_kelas !== (string) $idKelasWali) {
                return redirect()->back()->withErrors([
                    'error' => 'Akses ditolak! Anda hanya berwenang memverifikasi izin siswa dari kelas bimbingan Anda (' . ($idKelasWali ?: 'Belum diatur') . ').'
                ]);
            }
        }

        $leave->status_approval = $request->status_approval;
        $leave->verified_by = $idUser;

        // Simpan catatan hanya jika ditolak (jika disetujui, catatan lama dikosongkan)
        if ($this->pastikanKolomCatatan()) {
            $leave->catatan_verifikasi = $request->status_approval === 'Rejected'
                ? trim($request->catatan_verifikasi)
                : null;
        } elseif ($request->status_approval === 'Rejected') {
            return redirect()->back()->withErrors([
                'error' => 'Catatan gagal disimpan: kolom catatan_verifikasi tidak bisa dibuat di tabel leave_requests. Jalankan SQL: ALTER TABLE leave_requests ADD COLUMN catatan_verifikasi TEXT NULL;'
            ]);
        }

        $leave->save();

        $pesan = $request->status_approval === 'Rejected'
            ? 'Pengajuan izin ditolak dan catatan penolakan telah disimpan.'
            : 'Pengajuan izin berhasil disetujui!';

        return redirect()->back()->with('success', $pesan);
    }

    /**
     * Rekap Presensi Siswa untuk Waka Kurikulum, Guru BK, dan Wali Kelas
     * Bersumber dari data perizinan resmi siswa yang telah disetujui (Approved) oleh Wali Kelas
     */
    public function rekapPresensi(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idRole = (int) session('id_role');
        $idUser = session('id_user');

        if (!in_array($idRole, [3, 7, 4])) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Menu ini khusus untuk Waka Kurikulum, Guru BK, dan Wali Kelas.']);
        }

        $filterKelas = $request->get('kelas');
        $filterStart = $request->get('start_date');
        $filterEnd = $request->get('end_date');

        // Jika login sebagai Wali Kelas (id_role 3), batasi hanya pada kelas binaannya
        $waliKelasId = null;
        if ($idRole === 3) {
            $userWali = DB::table('users')->where('id_user', $idUser)->first();
            $waliKelasId = $userWali->id_kelas ?? null;
            // Kunci filter kelas ke kelas binaannya
            $filterKelas = $waliKelasId ?: '__NO_CLASS__';
        }

        $kelasList = collect();
        if (Schema::hasTable('kelas')) {
            $kQuery = Kelas::query();
            if (Schema::hasTable('jurusan')) {
                $kQuery->with('jurusan');
            }
            if ($idRole === 3) {
                // Untuk wali kelas, pilihan kelas hanya kelasnya sendiri
                if (!empty($waliKelasId)) {
                    $kQuery->where('id_kelas', $waliKelasId);
                } else {
                    $kQuery->whereRaw('1 = 0');
                }
            }
            $kelasList = $kQuery->get();

            // Urutkan kelas secara logis (berdasarkan tingkat dan id_jurusan)
            $kelasList = $kelasList->sortBy(function ($k) {
                return [
                    $k->tingkat ?? '',
                    $k->id_jurusan ?? $k->nama_kelas ?? '',
                    (int) $k->id_kelas,
                ];
            })->values();
        }

        if (Schema::hasTable('kelas') && !file_exists(storage_path('kelas_schema_dump.json'))) {
            @file_put_contents(storage_path('kelas_schema_dump.json'), json_encode([
                'kelas_columns' => Schema::getColumnListing('kelas'),
                'jurusan_columns' => Schema::hasTable('jurusan') ? Schema::getColumnListing('jurusan') : [],
                'kelas_rows' => DB::table('kelas')->take(10)->get(),
                'jurusan_rows' => Schema::hasTable('jurusan') ? DB::table('jurusan')->get() : [],
            ], JSON_PRETTY_PRINT));
        }

        // 1. Data Siswa Aktif
        $siswaQuery = Siswa::with(['kelas.jurusan'])->where('status_utama', 'Aktif');
        if (!empty($filterKelas)) {
            $siswaQuery->where('id_kelas', $filterKelas);
        }
        $siswaList = $siswaQuery->orderBy('id_kelas')->orderBy('nama_siswa')->get();

        // 2. Data Izin Siswa yang Telah Disetujui (Approved) oleh Wali Kelas / Guru BK
        $leavesQuery = LeaveRequest::with(['student.kelas.jurusan', 'verifier'])
            ->where('status_approval', 'Approved');

        if (!empty($filterStart)) {
            $leavesQuery->whereDate('end_date', '>=', $filterStart);
        }
        if (!empty($filterEnd)) {
            $leavesQuery->whereDate('start_date', '<=', $filterEnd);
        }
        if (!empty($filterKelas)) {
            $leavesQuery->whereHas('student', function ($q) use ($filterKelas) {
                $q->where('id_kelas', $filterKelas);
            });
        }

        $approvedLeaves = $leavesQuery->orderBy('start_date', 'desc')->get();

        // 3. Rekap Akumulasi per Siswa berdasarkan Izin yang Disetujui Wali Kelas
        foreach ($siswaList as $s) {
            $studentLeaves = $approvedLeaves->where('student_id', $s->id_siswa);

            $s->sakit_leaves = $studentLeaves->filter(fn($l) => preg_match('/sakit/i', $l->reason))->values();
            $s->izin_leaves = $studentLeaves->reject(fn($l) => preg_match('/sakit/i', $l->reason))->values();

            $s->total_sakit = $s->sakit_leaves->count();
            $s->total_izin = $s->izin_leaves->count();
            $s->total_pengajuan = $studentLeaves->count();

            // Total hari akumulasi izin/sakit
            $s->total_hari_absen = $studentLeaves->sum(function ($l) {
                if ($l->start_date && $l->end_date) {
                    $start = \Carbon\Carbon::parse($l->start_date);
                    $end = \Carbon\Carbon::parse($l->end_date);
                    return max(1, $start->diffInDays($end) + 1);
                }
                return 1;
            });

            // Riwayat izin resmi siswa yang sudah disetujui
            $s->riwayat_izin = $studentLeaves->values();
        }

        // 4. Hanya ambil siswa yang memiliki pengajuan izin yang SUDAH DISETUJUI oleh Wali Kelas atau Guru BK
        $siswaRekap = $siswaList->filter(function ($s) {
            return $s->total_pengajuan > 0;
        })->values();

        // Ringkasan untuk Stat Cards
        $totalSiswaIzin = $siswaRekap->count();
        $totalApprovedLeaves = $approvedLeaves->count();
        $totalSakit = $approvedLeaves->filter(fn($l) => preg_match('/sakit/i', $l->reason))->count();
        $totalIzin = $approvedLeaves->reject(fn($l) => preg_match('/sakit/i', $l->reason))->count();

        // Total hari seluruh izin/sakit yang disetujui
        $totalHariAbsen = $approvedLeaves->sum(function ($l) {
            if ($l->start_date && $l->end_date) {
                $start = \Carbon\Carbon::parse($l->start_date);
                $end = \Carbon\Carbon::parse($l->end_date);
                return max(1, $start->diffInDays($end) + 1);
            }
            return 1;
        });

        // Siswa yang perlu perhatian (izin/sakit >= 3 kali atau total hari >= 5)
        $totalPerhatian = $siswaRekap->filter(function ($s) {
            return $s->total_pengajuan >= 3 || $s->total_hari_absen >= 5;
        })->count();

        return view('kesiswaan.presensi.rekap', [
            'siswaList' => $siswaRekap,
            'kelasList' => $kelasList,
            'approvedLeaves' => $approvedLeaves,
            'filterKelas' => $filterKelas,
            'filterStart' => $filterStart,
            'filterEnd' => $filterEnd,
            'totalSiswaIzin' => $totalSiswaIzin,
            'totalApprovedLeaves' => $totalApprovedLeaves,
            'totalSakit' => $totalSakit,
            'totalIzin' => $totalIzin,
            'totalHariAbsen' => $totalHariAbsen,
            'totalPerhatian' => $totalPerhatian,
            'isWaliKelas' => $idRole === 3,
            'waliKelasId' => $waliKelasId,
        ]);
    }
}