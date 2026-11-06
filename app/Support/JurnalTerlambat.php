<?php

namespace App\Support;

use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Mendeteksi jurnal mengajar HARI INI yang belum diisi padahal batas waktunya terlewat.
 *
 * Notifikasi muncul begitu jam selesai jadwal terlewat dan jurnal belum diisi.
 * Status tiap item:
 *   - 'segera'   : masih dalam toleransi, jurnal MASIH bisa diisi
 *   - 'terlewat' : toleransi habis, jurnal sudah tidak bisa diisi
 * Toleransi (menit setelah jam selesai) HARUS sama dengan batas pengisian di JurnalController.
 */
class JurnalTerlambat
{
    /** Toleransi pengisian jurnal (menit) setelah jam selesai. Dipakai juga oleh JurnalController. */
    public const TOLERANSI_MENIT = 30;

    /**
     * @param int|null $idGuru  null = semua guru (untuk Waka), isi = hanya guru tersebut
     * @return Collection<int, array>  diurutkan menurut nama guru lalu jam selesai
     */
    public static function hariIni(?int $idGuru = null): Collection
    {
        $now = now(); // zona waktu mengikuti config/app.php (Asia/Jakarta)
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
        $hari = $namaHari[$now->dayOfWeekIso] ?? null;
        if (!$hari) {
            return collect(); // Minggu tidak ada jadwal
        }

        // active_cycle bisa tersimpan "1" atau "Putaran 1" -> samakan jadi angka
        $normalize = fn($v) => preg_replace('/\D/', '', (string) $v) ?: '1';
        $config = SystemConfiguration::first();
        $activeCycle = $normalize($config->active_cycle ?? '1');

        $query = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru'])
            ->where('hari', $hari)
            ->whereNotNull('jam_selesai');
        if ($idGuru !== null) {
            $query->where('id_guru', $idGuru);
        }

        $jadwals = $query->get()
            ->filter(fn($j) => $normalize($j->cycle_type) === $activeCycle);

        if ($jadwals->isEmpty()) {
            return collect();
        }

        $today = $now->toDateString();
        $sudahIsi = Journal::whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->whereDate('date', $today)
            ->pluck('schedule_id')
            ->map(fn($id) => (string) $id)
            ->all();

        $hasil = [];
        foreach ($jadwals as $j) {
            if (in_array((string) $j->id_jadwal, $sudahIsi, true)) {
                continue;
            }

            $selesai = Carbon::parse($today . ' ' . $j->jam_selesai);
            if ($now->lt($selesai)) {
                continue; // pelajaran belum selesai
            }
            $batas = $selesai->copy()->addMinutes(self::TOLERANSI_MENIT);
            $masihBisa = $now->lte($batas);

            $hasil[] = [
                'id_jadwal'   => $j->id_jadwal,
                'id_guru'     => $j->id_guru,
                'guru'        => $j->guru->nama_guru ?? ('Guru #' . $j->id_guru),
                'nip'         => $j->guru->nip ?? '',
                'kelas'       => $j->kelas->nama_kelas ?? $j->id_kelas,
                'mapel'       => $j->mataPelajaran->nama_mapel ?? $j->id_mapel,
                'jam_mulai'   => substr((string) $j->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $j->jam_selesai, 0, 5),
                'batas'       => $batas->format('H:i'),
                'status'      => $masihBisa ? 'segera' : 'terlewat',
                'sisa_menit'  => $masihBisa ? max(1, (int) ceil(($batas->timestamp - $now->timestamp) / 60)) : 0,
                'telat_menit' => intdiv($now->timestamp - $selesai->timestamp, 60), // sejak jam selesai
            ];
        }

        return collect($hasil)
            ->sortBy([['guru', 'asc'], ['jam_selesai', 'asc']])
            ->values();
    }

    /** Teks durasi terlambat, mis. "1 jam 20 menit". */
    public static function formatTelat(int $menit): string
    {
        $menit = max(0, $menit);
        $j = intdiv($menit, 60);
        $m = $menit % 60;
        if ($j > 0 && $m > 0) return "{$j} jam {$m} menit";
        if ($j > 0) return "{$j} jam";
        return "{$m} menit";
    }
}
