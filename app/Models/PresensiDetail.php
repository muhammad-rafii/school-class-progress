<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiDetail extends Model
{
    protected $table = 'presensi_detail';
    protected $primaryKey = 'id_detail';
    public $timestamps = false;
    protected $guarded = [];

    public function header()
    {
        return $this->belongsTo(PresensiHeader::class, 'id_presensi');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa');
    }
}

