<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = DB::table('users')->where('username', $request->username)->first();

        if ($user && Hash::check($request->password, $user->password_hash)) {
            session([
                'id_user' => $user->id_user,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'id_role' => $user->id_role,
                'foto_profil' => $user->foto_profil ?? null,
            ]);

            // REDIRECT BERDASARKAN ID_ROLE SECARA SPESIFIK
            // 4 = Waka Kurikulum
            // 3 = Wali Kelas (ke verifikasi izin kesiswaan)
            // 2 = Guru, 7 = BK
            // 5 = Siswa (atau 1)
            switch ($user->id_role) {
                case 4:
                    return redirect()->route('waka.dashboard')->with('success', 'Berhasil login sebagai Waka Kurikulum!');
                case 3:
                    return redirect()->route('kesiswaan.izin.index')->with('success', 'Berhasil login sebagai Wali Kelas!');
                case 7:
                    return redirect()->route('kesiswaan.izin.index')->with('success', 'Berhasil login sebagai Guru BK!');
                case 2:
                    return redirect()->route('guru.dashboard')->with('success', 'Berhasil login!');
                case 1:
                case 5:
                    return redirect()->route('siswa.izin.index')->with('success', 'Berhasil login sebagai Siswa!');
                default:
                    return redirect()->route('login')->withErrors(['username' => 'Role pengguna tidak dikenali sistem.']);
            }
        }

        return back()->withErrors(['username' => 'Username atau password salah.']);
    }

    public function showRegisterForm()
    {
        $kelasList = DB::table('kelas')->get();
        return view('auth.register', compact('kelasList'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'     => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'password'     => 'required|string|min:6',
            'no_hp'        => 'nullable|string|max:15',
            'email'        => 'nullable|email|max:100|unique:users,email',
            'id_kelas'     => 'nullable|string',
        ]);

        DB::table('users')->insert([
            'username'      => $request->username,
            'nama_lengkap'  => $request->nama_lengkap,
            'password_hash' => Hash::make($request->password),
            'no_hp'         => $request->no_hp,
            'email'         => $request->filled('email') ? strtolower(trim($request->email)) : null,
            'id_role'       => 5, // Otomatis diatur sebagai Siswa
            'is_aktif'      => 1,
        ]);

        // Otomatis masukkan ke tabel siswa jika belum ada
        $cekSiswa = DB::table('siswa')->where('nisn', $request->username)->exists();
        if (!$cekSiswa) {
            DB::table('siswa')->insert([
                'nisn'         => $request->username,
                'nama_siswa'   => $request->nama_lengkap,
                'status_utama' => 'Aktif',
                'id_kelas'     => $request->id_kelas ?: null,
            ]);
        }

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }

    // ==========================================================
    // LUPA PASSWORD VIA OTP EMAIL (GMAIL)
    // Alur: 1) masukkan username -> OTP dikirim ke email terdaftar
    //       2) masukkan kode OTP -> 3) buat password baru
    // Data OTP disimpan di session (hash), tanpa tabel tambahan.
    // ==========================================================
    private const OTP_TTL_MINUTES      = 10;  // masa berlaku kode OTP
    private const OTP_MAX_ATTEMPTS     = 5;   // salah memasukkan OTP maksimal
    private const OTP_RESEND_SECONDS   = 60;  // jeda kirim ulang
    private const VERIFIED_TTL_MINUTES = 15;  // waktu untuk ganti password setelah OTP benar

    private const FP_KEYS = [
        'fp_user_id', 'fp_email', 'fp_otp_hash', 'fp_otp_expires',
        'fp_attempts', 'fp_last_sent', 'fp_verified_until',
    ];

    public function showForgotPasswordForm()
    {
        $step = 'email';
        if (session('fp_user_id')) {
            if (session('fp_verified_until') && now()->timestamp < (int) session('fp_verified_until')) {
                $step = 'reset';
            } elseif (session('fp_otp_hash')) {
                $step = 'otp';
            }
        }

        $maskedEmail = session('fp_email') ? $this->maskEmail(session('fp_email')) : null;
        $resendIn = max(0, self::OTP_RESEND_SECONDS - (now()->timestamp - (int) session('fp_last_sent', 0)));

        return view('auth.forgot-password', compact('step', 'maskedEmail', 'resendIn'));
    }

    /** Langkah 1: cari akun berdasarkan username + email, lalu kirim OTP */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'email'    => 'required|email',
        ], [
            'username.required' => 'Username / NISN / NIP wajib diisi.',
            'email.required'    => 'Alamat Gmail wajib diisi.',
            'email.email'       => 'Format Gmail tidak valid.',
        ]);

        $user = DB::table('users')->where('username', trim($request->username))->first();

        if (!$user) {
            return back()->withErrors(['username' => 'Akun dengan Username / NISN / NIP tersebut tidak ditemukan.'])->withInput();
        }

        // Verifikasi: email yang dimasukkan harus cocok dengan email di database
        if (empty($user->email)) {
            return back()->withErrors(['email' => 'Akun ini belum memiliki email terdaftar. Hubungi Waka Kurikulum atau pihak sekolah untuk mendaftarkan email akun Anda.'])->withInput();
        }

        if (strtolower(trim($request->email)) !== strtolower(trim($user->email))) {
            return back()->withErrors(['email' => 'Email tidak sesuai dengan yang terdaftar di akun ini. Pastikan email yang Anda masukkan benar.'])->withInput();
        }

        // Jeda kirim ulang untuk akun yang sama
        if ((int) session('fp_user_id') === (int) $user->id_user) {
            $wait = self::OTP_RESEND_SECONDS - (now()->timestamp - (int) session('fp_last_sent', 0));
            if ($wait > 0) {
                return back()->withErrors(['username' => "Tunggu {$wait} detik sebelum meminta kode OTP lagi."])->withInput();
            }
        }

        return $this->issueOtp($user);
    }

    /** Kirim ulang OTP (akun diambil dari session) */
    public function resendOtp()
    {
        $user = session('fp_user_id')
            ? DB::table('users')->where('id_user', session('fp_user_id'))->first()
            : null;

        if (!$user || empty($user->email)) {
            $this->clearForgotSession();
            return redirect()->route('password.request')->withErrors(['username' => 'Sesi habis, silakan mulai lagi.']);
        }

        $wait = self::OTP_RESEND_SECONDS - (now()->timestamp - (int) session('fp_last_sent', 0));
        if ($wait > 0) {
            return back()->withErrors(['otp' => "Tunggu {$wait} detik sebelum meminta kode OTP lagi."]);
        }

        return $this->issueOtp($user);
    }

    /** Langkah 2: periksa kode OTP */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus 6 digit angka.',
        ]);

        if (!session('fp_user_id') || !session('fp_otp_hash')) {
            $this->clearForgotSession();
            return redirect()->route('password.request')->withErrors(['username' => 'Sesi habis, silakan minta kode OTP lagi.']);
        }

        if (now()->timestamp > (int) session('fp_otp_expires')) {
            $this->clearForgotSession();
            return redirect()->route('password.request')->withErrors(['username' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.']);
        }

        if ((int) session('fp_attempts', 0) >= self::OTP_MAX_ATTEMPTS) {
            $this->clearForgotSession();
            return redirect()->route('password.request')->withErrors(['username' => 'Terlalu banyak percobaan salah. Silakan minta kode OTP baru.']);
        }

        if (!Hash::check($request->otp, session('fp_otp_hash'))) {
            $attempts = (int) session('fp_attempts', 0) + 1;
            session(['fp_attempts' => $attempts]);
            $sisa = self::OTP_MAX_ATTEMPTS - $attempts;

            if ($sisa <= 0) {
                $this->clearForgotSession();
                return redirect()->route('password.request')->withErrors(['username' => 'Terlalu banyak percobaan salah. Silakan minta kode OTP baru.']);
            }

            return back()->withErrors(['otp' => "Kode OTP salah. Sisa percobaan: {$sisa}."]);
        }

        // OTP benar: hapus OTP (sekali pakai), izinkan ganti password
        session()->forget(['fp_otp_hash', 'fp_otp_expires', 'fp_attempts']);
        session(['fp_verified_until' => now()->addMinutes(self::VERIFIED_TTL_MINUTES)->timestamp]);

        return redirect()->route('password.request')->with('success', 'Kode OTP benar. Silakan buat password baru.');
    }

    /** Langkah 3: simpan password baru (hanya setelah OTP terverifikasi) */
    public function resetPassword(Request $request)
    {
        if (!session('fp_user_id') || !session('fp_verified_until') || now()->timestamp > (int) session('fp_verified_until')) {
            $this->clearForgotSession();
            return redirect()->route('password.request')->withErrors(['username' => 'Verifikasi OTP belum dilakukan atau sudah kedaluwarsa. Silakan ulangi.']);
        }

        $request->validate([
            'password' => 'required|min:5|confirmed',
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password baru minimal 5 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        DB::table('users')->where('id_user', session('fp_user_id'))->update([
            'password_hash' => Hash::make($request->password),
        ]);

        $this->clearForgotSession();

        return redirect()->route('login')->with('success', 'Password berhasil direset! Silakan login dengan password baru Anda.');
    }

    /** Batalkan proses & kembali ke langkah 1 */
    public function cancelForgot()
    {
        $this->clearForgotSession();
        return redirect()->route('password.request');
    }

    private function issueOtp(object $user)
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            Mail::send('emails.otp', [
                'nama'    => $user->nama_lengkap,
                'otp'     => $otp,
                'menit'   => self::OTP_TTL_MINUTES,
            ], function ($message) use ($user) {
                $message->to($user->email)->subject('Kode OTP Reset Password - School Class Progress');
            });
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim OTP lupa password: ' . $e->getMessage());
            $this->clearForgotSession();
            return back()->withErrors(['username' => 'Gagal mengirim email OTP. Periksa pengaturan email server atau coba lagi nanti.'])->withInput();
        }

        session([
            'fp_user_id'     => $user->id_user,
            'fp_email'       => $user->email,
            'fp_otp_hash'    => Hash::make($otp),
            'fp_otp_expires' => now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp,
            'fp_attempts'    => 0,
            'fp_last_sent'   => now()->timestamp,
        ]);
        session()->forget('fp_verified_until');

        return redirect()->route('password.request')->with('success', 'Kode OTP telah dikirim ke ' . $this->maskEmail($user->email) . '. Periksa kotak masuk atau folder spam.');
    }

    private function clearForgotSession(): void
    {
        session()->forget(self::FP_KEYS);
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, min(2, max(1, mb_strlen($name) - 1)));
        return $visible . str_repeat('*', max(3, mb_strlen($name) - mb_strlen($visible))) . '@' . $domain;
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('login')->with('success', 'Berhasil logout!');
    }
}