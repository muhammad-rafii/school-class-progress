<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id_jurusan');
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }

    public function jadwal()
    {
        return $this->hasMany(JadwalPelajaran::class, 'id_kelas', 'id_kelas');
    }

    /**
     * Accessor untuk nama kelas yang lengkap & informatif (bukan sekadar id_kelas)
     */
    public function getNamaKelasAttribute($value)
    {
        // 1. Jika kolom 'nama_kelas' ada di tabel dan terisi
        if (!empty($value)) {
            return $value;
        }

        $tingkat = trim($this->attributes['tingkat'] ?? '');

        // 2. Ambil dari relasi jurusan atau kolom id_jurusan
        $jurusanText = null;
        if ($this->relationLoaded('jurusan') && $this->jurusan) {
            $jurusanText = $this->jurusan->nama_jurusan ?: $this->jurusan->id_jurusan;
        } elseif (!empty($this->attributes['id_jurusan'])) {
            try {
                $j = Jurusan::find($this->attributes['id_jurusan']);
                $jurusanText = $j->nama_jurusan ?? ($j->id_jurusan ?? $this->attributes['id_jurusan']);
            } catch (\Throwable $e) {
                $jurusanText = $this->attributes['id_jurusan'];
            }
        }

        if (!empty($jurusanText)) {
            // Jika jurusanText sudah memuat tingkat (misal "X RPL 1")
            if (!empty($tingkat) && stripos($jurusanText, $tingkat) === 0) {
                return $jurusanText;
            }
            return trim("{$tingkat} {$jurusanText}");
        }

        if (!empty($this->attributes['nama'])) {
            return trim("{$tingkat} " . $this->attributes['nama']);
        }

        // 3. Fallback jika tidak ada data jurusan
        $id = $this->attributes['id_kelas'] ?? '';
        return !empty($tingkat) ? trim("{$tingkat} {$id}") : $id;
    }
}
