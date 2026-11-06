<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UKK SCP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh; padding: 20px 0;">
    <div class="card shadow-sm p-4" style="width: 440px;">
        <h3 class="text-center mb-1">Register UKK SCP</h3>
        <p class="text-center text-muted small mb-4">Pendaftaran Akun Siswa Baru</p>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Username / NISN <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required value="{{ old('username') }}" placeholder="Masukkan NISN">
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama_lengkap" class="form-control" required value="{{ old('nama_lengkap') }}" placeholder="Masukkan nama lengkap siswa">
            </div>
            <div class="mb-3">
                <label class="form-label">Kelas Siswa</label>
                <select name="id_kelas" class="form-select">
                    <option value="">-- Pilih Kelas Anda (Opsional) --</option>
                    @if(isset($kelasList))
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id_kelas }}" {{ old('id_kelas') == $k->id_kelas ? 'selected' : '' }}>
                                {{ $k->nama_kelas ?? $k->id_kelas }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">No HP / WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}" placeholder="Contoh: 081234567890">
            </div>
            <div class="mb-3">
                <label class="form-label">Email (Gmail)</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="contoh: nama@gmail.com">
                <div class="form-text">Dipakai untuk kode OTP jika lupa password.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="6" placeholder="Ulangi password">
            </div>
            <button type="submit" class="btn btn-success w-100 mb-3">Register</button>
            <p class="text-center mb-0">Sudah punya akun? <a href="{{ route('login') }}">Login</a></p>
        </form>
    </div>
</body>
</html>