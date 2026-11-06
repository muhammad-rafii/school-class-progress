<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — School Class Progress</title>
    <meta name="description" content="Reset password akun Anda di sistem School Class Progress.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --accent: #06b6d4;
            --danger: #ef4444;
            --success: #10b981;
            --bg: #ffffff;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text: #0f172a;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #cbd5e1;
            --input-focus: rgba(99, 102, 241, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            background-image: none;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            color: var(--text);
            padding: 30px 16px;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            animation: slideUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px 36px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 82px; height: 82px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.12);
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-header h1 {
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .login-header p {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 400;
            line-height: 1.45;
        }

        /* --- Alerts --- */
        .alert {
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert ul { list-style: none; padding: 0; margin: 0; }
        .alert ul li + li { margin-top: 4px; }

        /* --- Form --- */
        .form-group { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 7px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .input-wrapper { position: relative; }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.25s;
        }

        .form-input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            color: #0f172a;
            font-family: 'Inter', sans-serif;
            font-size: 0.9375rem;
            padding: 12px 14px 12px 42px;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
        }

        .form-input::placeholder { color: #94a3b8; }

        .form-input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px var(--input-focus);
        }

        .input-wrapper:focus-within .input-icon { color: var(--primary); }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            transition: color 0.2s;
            line-height: 1;
        }

        .toggle-password:hover { color: #0f172a; }
        .form-input.has-toggle { padding-right: 42px; }

        /* --- Submit Button --- */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.9375rem;
            font-weight: 600;
            padding: 13px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
            margin-top: 10px;
            letter-spacing: 0.3px;
        }

        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.45); }
        .btn-submit:active { transform: translateY(0); }

        .form-info {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 4px;
        }

        .login-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .login-footer a:hover { color: var(--primary-dark); text-decoration: underline; }

        .help-box {
            margin-top: 20px;
            padding: 12px 14px;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            font-size: 0.78rem;
            color: #64748b;
            text-align: center;
            line-height: 1.45;
        }

        @media (max-width: 480px) {
            .login-card { padding: 30px 20px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="login-logo">
                <div class="logo-icon">
                    <img src="{{ asset('logo-smk.png.png') }}" alt="Logo SMK Negeri 1 Bantul">
                </div>
            </div>

            <div class="login-header">
                <h1>Lupa Password</h1>
                <p>
                    @if($step === 'email') Masukkan Username/NISN/NIP dan Gmail yang terdaftar. Kode OTP akan dikirim ke Gmail tersebut.
                    @elseif($step === 'otp') Masukkan 6 digit kode OTP yang dikirim ke <strong>{{ $maskedEmail }}</strong>.
                    @else Buat password baru untuk akun Anda.
                    @endif
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill" style="flex-shrink:0;margin-top:1px;"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px;"></i>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($step === 'email')
                {{-- LANGKAH 1: minta NISN/NIP + Gmail --}}
                <form action="{{ route('password.otp.send') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="form-group">
                        <label for="username">Username / NISN / NIP <span style="color:var(--danger)">*</span></label>
                        <div class="input-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input id="username" type="text" name="username" class="form-input {{ $errors->has('username') ? 'is-invalid' : '' }}"
                                placeholder="Masukkan Username, NISN, atau NIP" required value="{{ old('username') }}">
                        </div>
                        @error('username')
                            <div style="color:var(--danger);font-size:.8rem;margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Alamat Gmail Terdaftar <span style="color:var(--danger)">*</span></label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input id="email" type="email" name="email" class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                placeholder="nama@gmail.com" required value="{{ old('email') }}">
                        </div>
                        @error('email')
                            <div style="color:var(--danger);font-size:.8rem;margin-top:4px;">{{ $message }}</div>
                        @enderror
                        <div class="form-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Masukkan Gmail yang terdaftar pada akun Anda. Kode OTP 6 digit akan dikirim ke Gmail tersebut.
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-envelope-paper-fill me-1"></i> Kirim Kode OTP ke Gmail
                    </button>
                </form>

            @elseif($step === 'otp')
                {{-- LANGKAH 2: verifikasi OTP --}}
                <form action="{{ route('password.otp.verify') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="form-group">
                        <label for="otp">Kode OTP</label>
                        <div class="input-wrapper">
                            <i class="bi bi-shield-lock input-icon"></i>
                            <input id="otp" type="text" name="otp" class="form-input" inputmode="numeric"
                                pattern="[0-9]{6}" maxlength="6" placeholder="6 digit kode OTP" required autofocus
                                style="letter-spacing:6px;font-weight:600;">
                        </div>
                        <div class="form-info">Berlaku 10 menit. Cek juga folder spam.</div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check2-circle me-1"></i> Verifikasi Kode
                    </button>
                </form>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:.82rem;">
                    <form action="{{ route('password.otp.resend') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" id="btnResend" style="background:none;border:none;color:var(--primary);font-weight:600;cursor:pointer;padding:0;" {{ $resendIn > 0 ? 'disabled' : '' }}>
                            Kirim ulang kode <span id="resendTimer">{{ $resendIn > 0 ? '(' . $resendIn . 's)' : '' }}</span>
                        </button>
                    </form>
                    <form action="{{ route('password.otp.cancel') }}" method="POST" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:0;">Ganti akun</button>
                    </form>
                </div>

            @else
                {{-- LANGKAH 3: password baru --}}
                <form action="{{ route('password.update') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input id="password" type="password" name="password" class="form-input has-toggle"
                                placeholder="Minimal 5 karakter" required minlength="5">
                            <button type="button" class="toggle-password" onclick="togglePass('password', 'eye1')">
                                <i class="bi bi-eye" id="eye1"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock-fill input-icon"></i>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-input has-toggle"
                                placeholder="Ulangi password baru" required minlength="5">
                            <button type="button" class="toggle-password" onclick="togglePass('password_confirmation', 'eye2')">
                                <i class="bi bi-eye" id="eye2"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-key-fill me-1"></i> Simpan Password Baru
                    </button>
                </form>
            @endif

            <div class="help-box">
                <i class="bi bi-info-circle me-1 text-primary"></i>
                Belum punya email terdaftar di akun? Hubungi <strong>Waka Kurikulum</strong> atau pihak sekolah untuk mendaftarkannya melalui menu <em>Manajemen Akun</em>.
            </div>

            <p class="login-footer">
                Sudah ingat password? <a href="{{ route('login') }}">Masuk di sini</a>
            </p>

        </div>
    </div>

    <script>
        // Hitung mundur tombol "Kirim ulang kode"
        (function () {
            let left = {{ (int) ($resendIn ?? 0) }};
            const btn = document.getElementById('btnResend');
            const label = document.getElementById('resendTimer');
            if (!btn || left <= 0) return;
            const t = setInterval(() => {
                left--;
                if (left <= 0) { clearInterval(t); btn.disabled = false; label.textContent = ''; }
                else { label.textContent = '(' + left + 's)'; }
            }, 1000);
        })();

        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>