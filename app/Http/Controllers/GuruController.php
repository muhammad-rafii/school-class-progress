<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guru;
use App\Support\JurnalTerlambat;

class GuruController extends Controller
{
    public function dashboard()
    {
        // Pengaman anti-bobol: Khusus Guru Pengampu (2) dan Wali Kelas (3)
        $roleId = (int)session('id_role');
        if (!session()->has('id_user') || !in_array($roleId, [2, 3])) {
            if ($roleId === 7) {
                return redirect()->route('kesiswaan.izin.index');
            }
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Guru Pengampu & Wali Kelas.']);
        }

        // Notifikasi: jurnal hari ini yang belum diisi setelah 5 jam pelajaran sejak jadwal selesai
        $jurnalTerlambat = collect();
        $guru = Guru::where('nip', session('username'))->first();
        if ($guru) {
            $jurnalTerlambat = JurnalTerlambat::hariIni((int) $guru->id_guru);
        }

        return view('guru.dashboard', compact('jurnalTerlambat'));
    }
}
