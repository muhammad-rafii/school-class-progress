<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\SiswaIzinController;
use App\Http\Controllers\KesiswaanController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// ROUTE AUTENTIKASI & LOGOUT
// ==========================================
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/register-process', [AuthController::class, 'register'])->name('register.post');

Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp'])->name('password.otp.send');
Route::post('/forgot-password/resend-otp', [AuthController::class, 'resendOtp'])->name('password.otp.resend');
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp'])->name('password.otp.verify');
Route::post('/forgot-password/cancel', [AuthController::class, 'cancelForgot'])->name('password.otp.cancel');

// Route Logout yang terhubung dengan tombol logout baru
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ==========================================
// ROUTE WAKA KURIKULUM
// ==========================================
Route::get('/waka/dashboard', [WakaController::class, 'dashboard'])->name('waka.dashboard');
Route::post('/waka/config/update', [WakaController::class, 'updateConfig'])->name('waka.config.update');
Route::get('/waka/jurnal/rekap', [JurnalController::class, 'rekapWaka'])->name('waka.jurnal.rekap');


// ==========================================
// ROUTE MANAJEMEN AKUN & ROLE
// ==========================================
Route::get('/manajemen-akun', [JadwalPelajaranController::class, 'indexAkun'])->name('akun.index');
Route::post('/manajemen-akun/store', [JadwalPelajaranController::class, 'storeAkun'])->name('akun.store');
Route::post('/manajemen-akun/update-kelas', [JadwalPelajaranController::class, 'updateKelasSiswa'])->name('akun.updateKelas');
Route::post('/manajemen-akun/update-role', [JadwalPelajaranController::class, 'updateRole'])->name('akun.updateRole');
Route::delete('/manajemen-akun/{id}/destroy', [JadwalPelajaranController::class, 'destroyAkun'])->name('akun.destroy');


// ==========================================
// ROUTE GURU
// ==========================================
Route::get('/guru/dashboard', [GuruController::class, 'dashboard'])->name('guru.dashboard');


// ==========================================
// ROUTE JADWAL
// ==========================================
Route::get('/jadwal', [JadwalPelajaranController::class, 'index'])->name('jadwal.index');
Route::post('/jadwal/store', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
Route::post('/jadwal/config/update', [JadwalPelajaranController::class, 'updateConfig'])->name('jadwal.config.update');


// ==========================================
// ROUTE JURNAL
// ==========================================
Route::get('/jurnal', [JurnalController::class, 'index'])->name('jurnal.index');
Route::post('/jurnal/store', [JurnalController::class, 'store'])->name('jurnal.store');
Route::post('/jurnal/presensi/update', [JurnalController::class, 'updatePresensi'])->name('jurnal.presensi.update');
Route::get('/jurnal/pengingat', [JurnalController::class, 'pengingat'])->name('jurnal.pengingat');


// ==========================================
// ROUTE SISWA (PENGAJUAN IZIN / SAKIT)
// ==========================================
Route::get('/siswa/izin', [SiswaIzinController::class, 'index'])->name('siswa.izin.index');
Route::post('/siswa/izin', [SiswaIzinController::class, 'store'])->name('siswa.izin.store');


// ==========================================
// ROUTE KESISWAAN (WALI KELAS & GURU BK) - VERIFIKASI IZIN & REKAP PRESENSI
// ==========================================
Route::get('/kesiswaan/izin', [KesiswaanController::class, 'index'])->name('kesiswaan.izin.index');
Route::post('/kesiswaan/izin/{id}/update', [KesiswaanController::class, 'updateStatus'])->name('kesiswaan.izin.update');
Route::get('/kesiswaan/rekap-presensi', [KesiswaanController::class, 'rekapPresensi'])->name('kesiswaan.presensi.rekap');


// ==========================================
// ROUTE PROFIL PENGGUNA (SEMUA ROLE)
// ==========================================
Route::get('/profil', [ProfileController::class, 'index'])->name('profile.index');
Route::post('/profil/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
Route::post('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::post('/profil/foto', [ProfileController::class, 'updateFoto'])->name('profile.photo');
Route::post('/profil/foto/hapus', [ProfileController::class, 'hapusFoto'])->name('profile.photo.delete');


// ==========================================
// ROUTE STATIC STORAGE (Fallback Windows)
// ==========================================
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }

    $fallbackPath = public_path($path);
    if (file_exists($fallbackPath)) {
        return response()->file($fallbackPath);
    }

    abort(404);
})->where('path', '.*');