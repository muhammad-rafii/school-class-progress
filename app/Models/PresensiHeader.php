<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiHeader extends Model
{
    protected $table = 'presensi_header';
    protected $primaryKey = 'id_presensi';
    public $timestamps = false;
    protected $guarded = [];

    public function jadwal()
    {
        return $this->belongsTo(JadwalPelajaran::class, 'id_jadwal');
    }

    public function details()
    {
        return $this->hasMany(PresensiDetail::class, 'id_presensi');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru_pengajar');
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_digunakan');
    }
}

