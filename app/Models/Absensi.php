<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $fillable = [
        'karyawan_id', 'tanggal', 'jam_masuk', 'jam_pulang',
        'metode', 'latitude', 'longitude', 'foto_selfie', 'status'
    ];

    // Relasi balik ke Karyawan
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}