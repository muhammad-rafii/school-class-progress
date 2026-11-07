<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — School Class Progress</title>
    <meta name="description" content="Masuk ke sistem School Class Progress untuk memantau perkembangan kelas secara real-time.">
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
        }

        /* --- Card --- */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px 16px;
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
            padding: 44px 38px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 88px; height: 88px;
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
            margin-bottom: 32px;
        }

        .login-header h1 {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #0f172a;
            -webkit-text-fill-color: #0f172a;
            margin-bottom: 6px;
        }

        .login-header p {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 400;
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

        .alert ul { list-style: none; padding: 0; }
        .alert ul li + li { margin-top: 4px; }

        /* --- Form --- */
        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
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
            padding: 13px 14px 13px 42px;
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

        .forgot-link {
            font-size: 0.8rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* --- Submit Button --- */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.9375rem;
            font-weight: 600;
            padding: 14px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
            margin-top: 8px;
            letter-spacing: 0.3px;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.45); }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        .btn-login .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple { to { transform: scale(4); opacity: 0; } }

        .btn-login .spinner {
            display: none;
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-login.loading .btn-text { display: none; }
        .btn-login.loading .spinner { display: block; }

        .login-footer {
            text-align: center;
            margin-top: 24px;
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

        @media (max-width: 480px) {
            .login-card { padding: 32px 24px; }
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
                <h1>Selamat Datang</h1>
                <p>Masuk ke School Class Progress</p>
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

            <form id="loginForm" action="{{ route('login.post') }}" method="POST" autocomplete="off">
                @csrf

                <div class="form-group">
                    <label for="username">NISN / NIP</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            class="form-input"
                            placeholder="Masukkan NISN atau NIP"
                            required
                            value="{{ old('username') }}"
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label for="password" style="margin-bottom: 0;">Password</label>
                        <a href="{{ route('password.request') }}" class="forgot-link">Lupa Password?</a>
                    </div>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="form-input has-toggle"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Tampilkan password">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">Masuk Sekarang</span>
                    <span class="spinner"></span>
                </button>
            </form>

        </div>
    </div>

    <script>
        // Toggle password
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // Ripple
        const loginBtn = document.getElementById('loginBtn');
        loginBtn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            const size = Math.max(rect.width, rect.height);
            ripple.classList.add('ripple');
            ripple.style.cssText = `width:${size}px;height:${size}px;left:${e.clientX - rect.left - size / 2}px;top:${e.clientY - rect.top - size / 2}px;`;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 700);
        });

        // Loading on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });
    </script>
</body>
</html>
