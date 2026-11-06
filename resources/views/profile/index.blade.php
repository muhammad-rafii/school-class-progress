@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('page-title', 'Profil Pengguna')
@section('breadcrumb', 'Akun')
@section('meta_description', 'Kelola informasi profil akun dan kata sandi Anda.')

@php
    $roleName = match((int)$user->id_role) {
        3 => 'Wali Kelas & Guru Pengampu',
        default => ($role->nama_role ?? ($navRole['label'] ?? 'Pengguna'))
    };
    $parts = preg_split('/\s+/', trim($user->nama_lengkap ?? $user->username));
    $initials = strtoupper(mb_substr($parts[0] ?? 'U', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
@endphp

@section('content')
    {{-- ===== HERO PROFIL ===== --}}
    <section class="page-hero mb-4">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="avatar" style="width: 72px; height: 72px; font-size: 1.8rem; border-radius: 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                @if(!empty($user->foto_profil))
                    <img src="{{ asset($user->foto_profil) }}" alt="Foto profil" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div>
                <span class="hero-eyebrow"><i class="fa-solid fa-id-card-clip"></i> Akun Terdaftar</span>
                <h2 class="mb-1">{{ $user->nama_lengkap }}</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="role-pill"><i class="fa-solid {{ $navRole['icon'] ?? 'fa-user' }}"></i> {{ $roleName }}</span>
                    <span class="badge bg-white-subtle text-white border border-white-subtle px-2 py-1" style="font-size: 0.8rem;">
                        <i class="fa-regular fa-user me-1"></i> Username: {{ $user->username }}
                    </span>
                    @if($user->is_aktif)
                        <span class="badge bg-success-subtle text-success-emphasis border px-2 py-1" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-circle-check me-1"></i> Aktif
                        </span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <form action="{{ route('profile.photo') }}" method="POST" enctype="multipart/form-data" id="formFotoProfil">
                        @csrf
                        <input type="file" name="foto" id="inputFotoProfil" class="d-none" accept="image/jpeg,image/png,image/webp">
                        <button type="button" class="btn btn-sm btn-light" id="btnUbahFoto">
                            <i class="fa-solid fa-camera me-1"></i> {{ !empty($user->foto_profil) ? 'Ganti Foto' : 'Unggah Foto' }}
                        </button>
                    </form>
                    @if(!empty($user->foto_profil))
                        <form action="{{ route('profile.photo.delete') }}" method="POST" onsubmit="return confirm('Hapus foto profil?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-trash-can me-1"></i> Hapus Foto</button>
                        </form>
                    @endif
                </div>
                @error('foto')
                    <div class="small mt-2 text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $message }}</div>
                @enderror
            </div>
        </div>
    </section>

    <div class="row g-4 animate-in">
        {{-- ===== FORM INFORMASI DATA DIRI ===== --}}
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-blue"><i class="fa-solid fa-user-pen"></i></div>
                    <div>
                        <h2>Informasi Akun</h2>
                        <p>Perbarui nama lengkap dan nomor telepon kontak Anda</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="username">Username / Identitas Login</label>
                            <input type="text" id="username" class="form-control bg-light" value="{{ $user->username }}" readonly disabled>
                            <div class="form-text">Username digunakan untuk login dan tidak dapat diubah sendiri.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="nama_lengkap">Nama Lengkap @if((int) $user->id_role === 4)<span class="text-danger">*</span>@endif</label>
                            @if((int) $user->id_role === 4)
                                <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" required value="{{ old('nama_lengkap', $user->nama_lengkap) }}" placeholder="Masukkan nama lengkap">
                            @else
                                <input type="text" id="nama_lengkap" class="form-control bg-light" value="{{ $user->nama_lengkap }}" readonly>
                                <div class="form-text"><i class="fa-solid fa-lock me-1"></i>Nama lengkap hanya dapat diubah oleh Waka Kurikulum.</div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="no_hp">Nomor Handphone / WhatsApp</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                <input type="text" name="no_hp" id="no_hp" class="form-control" value="{{ old('no_hp', $user->no_hp) }}" placeholder="Contoh: 081234567890">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email (Gmail)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope text-danger"></i></span>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" placeholder="contoh: nama@gmail.com">
                            </div>
                            <div class="form-text">Dipakai untuk menerima kode OTP saat lupa password.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Peran dalam Sistem</label>
                            <input type="text" class="form-control bg-light" value="{{ $roleName }}" readonly disabled>
                            @if((int)$user->id_role === 3)
                                <div class="form-text text-success">
                                    <i class="fa-solid fa-circle-check me-1"></i> Akun memiliki 2 peran aktif: sebagai <strong>Guru Pengampu KBM</strong> (jadwal &amp; jurnal) dan <strong>Wali Kelas</strong>.
                                </div>
                            @endif
                        </div>

                        @if($extra || !empty($waliKelasInfo))
                            <div class="alert alert-light border mb-4">
                                <div class="small fw-semibold text-muted mb-1"><i class="fa-solid fa-circle-info text-primary me-1"></i> Detail Tambahan:</div>
                                @if((int)$user->id_role === 3 && !empty($waliKelasInfo))
                                    <div class="small mb-1">
                                        <strong>Kelas Binaan:</strong> 
                                        <span class="badge bg-primary text-white">
                                            {{ trim(($waliKelasInfo->tingkat ?? '') . ' ' . ($waliKelasInfo->id_jurusan ?? $waliKelasInfo->nama_kelas ?? $waliKelasInfo->id_kelas)) }}
                                        </span>
                                    </div>
                                @endif
                                @if(isset($extra->nip))
                                    <div class="small"><strong>NIP:</strong> {{ $extra->nip }}</div>
                                @endif
                                @if(isset($extra->nisn))
                                    <div class="small"><strong>NISN:</strong> {{ $extra->nisn }}</div>
                                @endif
                                @if(isset($extra->status_utama))
                                    <div class="small"><strong>Status Kesiswaan:</strong> {{ $extra->status_utama }}</div>
                                @endif
                            </div>
                        @endif

                        <button type="submit" class="btn btn-accent w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ===== FORM GANTI PASSWORD ===== --}}
        <div class="col-lg-6">
            <div class="card-modern h-100">
                <div class="card-modern-header">
                    <div class="icon-box solid tone-violet"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <h2>Ganti Kata Sandi</h2>
                        <p>Pastikan menggunakan kata sandi yang aman</p>
                    </div>
                </div>
                <div class="card-modern-body">
                    <form action="{{ route('profile.password') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="current_password">Password Saat Ini <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" class="form-control" required placeholder="Masukkan password saat ini">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('current_password', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Gunakan kombinasi huruf dan angka agar lebih aman.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required minlength="6" placeholder="Ulangi password baru">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password_confirmation', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-light border small text-muted mb-4">
                            <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>
                            Setelah password berhasil diubah, gunakan password baru tersebut untuk login berikutnya.
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-key me-1"></i> Perbarui Kata Sandi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const btn = document.getElementById('btnUbahFoto');
            const input = document.getElementById('inputFotoProfil');
            const form = document.getElementById('formFotoProfil');
            if (!btn || !input || !form) return;
            btn.addEventListener('click', () => input.click());
            input.addEventListener('change', () => {
                const f = input.files && input.files[0];
                if (!f) return;
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) {
                    alert('Format foto harus JPG, PNG, atau WEBP.');
                    input.value = '';
                    return;
                }
                if (f.size > 2 * 1024 * 1024) {
                    alert('Ukuran foto maksimal 2 MB.');
                    input.value = '';
                    return;
                }
                form.submit();
            });
        })();

        function togglePasswordVisibility(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
@endsection