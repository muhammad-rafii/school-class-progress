<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengecekan login + role terpusat.
 * Pemakaian di route: ->middleware('role:3,4,7')
 *
 * - Belum login            -> ke halaman login.
 * - Login tapi role salah  -> ke halaman utama role-nya sendiri (TIDAK logout).
 */
class EnsureRole
{
    /** Halaman utama tiap role (sama dengan redirect setelah login). */
    public const HOME = [
        4 => 'waka.dashboard',
        3 => 'kesiswaan.izin.index',
        7 => 'kesiswaan.izin.index',
        2 => 'guru.dashboard',
        1 => 'siswa.izin.index',
        5 => 'siswa.izin.index',
    ];

    public static function homeRoute(?int $roleId): string
    {
        return self::HOME[$roleId] ?? 'login';
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->session()->has('id_user')) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $roleId = (int) $request->session()->get('id_role');
        $allowed = array_map('intval', $roles);

        if (!in_array($roleId, $allowed, true)) {
            $nama = $request->session()->get('nama_lengkap', 'akun ini');

            return redirect()->route(self::homeRoute($roleId))
                ->withErrors(['error' => "Akses ditolak! Akun {$nama} tidak memiliki hak akses ke menu tersebut."]);
        }

        return $next($request);
    }
}
