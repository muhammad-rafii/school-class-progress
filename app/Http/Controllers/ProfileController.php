<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /** Pastikan kolom foto_profil ada di tabel users (sinkronisasi otomatis, seperti kolom lain di proyek ini). */
    private function pastikanKolomFoto(): bool
    {
        if (!Schema::hasTable('users')) {
            return false;
        }
        if (!Schema::hasColumn('users', 'foto_profil')) {
            try {
                Schema::table('users', function ($table) {
                    $table->string('foto_profil', 255)->nullable();
                });
            } catch (\Throwable $e) {
            }
        }
        return Schema::hasColumn('users', 'foto_profil');
    }

    /** Hapus file foto lama dari storage (hanya yang berada di uploads/profil). */
    private function hapusFileFoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/uploads/profil/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }

    public function index()
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $this->pastikanKolomFoto();

        $idUser = session('id_user');
        $user = DB::table('users')->where('id_user', $idUser)->first();

        if (!$user) {
            session()->flush();
            return redirect()->route('login')->withErrors(['error' => 'Data pengguna tidak ditemukan.']);
        }

        // Sinkronkan foto profil ke sesi agar tampil di sidebar & header
        session(['foto_profil' => $user->foto_profil ?? null]);

        $role = DB::table('role')->where('id_role', $user->id_role)->first();

        // Cari detail tambahan berdasarkan peran pengguna
        $extra = null;
        if (in_array($user->id_role, [2, 3, 7])) {
            // Guru, Wali Kelas, Guru BK
            if (Schema::hasTable('guru')) {
                $extra = DB::table('guru')->where('nip', $user->username)->first();
            }
        } elseif ($user->id_role == 5) {
            // Siswa
            if (Schema::hasTable('siswa')) {
                $extra = DB::table('siswa')->where('nisn', $user->username)->first();
            }
        }

        $waliKelasInfo = null;
        if ($user->id_role == 3 && !empty($user->id_kelas)) {
            $waliKelasInfo = DB::table('kelas')->where('id_kelas', $user->id_kelas)->first();
        }

        return view('profile.index', compact('user', 'role', 'extra', 'waliKelasInfo'));
    }

    public function updateProfile(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $idUser = session('id_user');
        $username = session('username');

        $current = DB::table('users')->where('id_user', $idUser)->first();
        if (!$current) {
            return redirect()->route('login')->withErrors(['error' => 'Data pengguna tidak ditemukan.']);
        }

        // Nama lengkap hanya boleh diubah oleh Waka Kurikulum (id_role = 4)
        $bolehUbahNama = (int) $current->id_role === 4;

        $request->validate([
            'nama_lengkap' => ($bolehUbahNama ? 'required' : 'nullable') . '|string|max:100',
            'no_hp'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100|unique:users,email,' . $idUser . ',id_user',
        ]);

        // Selain Waka, nama selalu memakai data yang sudah tersimpan (input dari form diabaikan)
        $namaFinal = $bolehUbahNama ? $request->nama_lengkap : $current->nama_lengkap;

        // Perbarui data di tabel users
        DB::table('users')->where('id_user', $idUser)->update([
            'nama_lengkap' => $namaFinal,
            'no_hp'        => $request->no_hp,
            'email'        => $request->filled('email') ? strtolower(trim($request->email)) : null,
        ]);

        // Perbarui sesi pengguna
        session(['nama_lengkap' => $namaFinal]);

        // Sinkronkan nama di tabel spesifik (Guru / Siswa) hanya jika nama memang boleh diubah
        if ($bolehUbahNama) {
            if (Schema::hasTable('guru')) {
                DB::table('guru')->where('nip', $username)->update([
                    'nama_guru' => $namaFinal,
                ]);
            }

            if (Schema::hasTable('siswa')) {
                DB::table('siswa')->where('nisn', $username)->update([
                    'nama_siswa' => $namaFinal,
                ]);
            }
        }

        return redirect()->route('profile.index')->with('success', 'Profil Anda berhasil diperbarui!');
    }

    public function updateFoto(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'foto.required' => 'Pilih foto terlebih dahulu.',
            'foto.image'    => 'File harus berupa gambar.',
            'foto.mimes'    => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'foto.max'      => 'Ukuran foto maksimal 2 MB.',
        ]);

        if (!$this->pastikanKolomFoto()) {
            return redirect()->back()->withErrors(['foto' => 'Kolom foto profil belum tersedia di database.']);
        }

        $idUser = session('id_user');
        $user = DB::table('users')->where('id_user', $idUser)->first();
        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'Data pengguna tidak ditemukan.']);
        }

        $file = $request->file('foto');
        $ext = strtolower($file->guessExtension() ?: 'jpg');
        $filename = 'u' . $idUser . '_' . time() . '.' . $ext;
        Storage::disk('public')->putFileAs('uploads/profil', $file, $filename);
        $path = 'storage/uploads/profil/' . $filename;

        $this->hapusFileFoto($user->foto_profil ?? null);
        DB::table('users')->where('id_user', $idUser)->update(['foto_profil' => $path]);
        session(['foto_profil' => $path]);

        return redirect()->route('profile.index')->with('success', 'Foto profil berhasil diperbarui!');
    }

    public function hapusFoto(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        if ($this->pastikanKolomFoto()) {
            $idUser = session('id_user');
            $user = DB::table('users')->where('id_user', $idUser)->first();
            if ($user) {
                $this->hapusFileFoto($user->foto_profil ?? null);
                DB::table('users')->where('id_user', $idUser)->update(['foto_profil' => null]);
            }
        }
        session(['foto_profil' => null]);

        return redirect()->route('profile.index')->with('success', 'Foto profil dihapus.');
    }

    public function updatePassword(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login');
        }

        $request->validate([
            'current_password'      => 'required',
            'password'              => 'required|min:6|confirmed',
        ], [
            'password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
            'password.min'          => 'Password baru minimal 6 karakter.',
        ]);

        $idUser = session('id_user');
        $user = DB::table('users')->where('id_user', $idUser)->first();

        if (!$user || !Hash::check($request->current_password, $user->password_hash)) {
            return redirect()->back()->withErrors(['current_password' => 'Password saat ini salah!'])->withInput();
        }

        DB::table('users')->where('id_user', $idUser)->update([
            'password_hash' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.index')->with('success', 'Password berhasil diubah. Gunakan password baru untuk login berikutnya!');
    }
}