<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;
    protected $table = 'absensis'; // Sesuaikan dengan nama tabel Anda
    protected $fillable = [
        // --- PASTIKAN USER_ID ADA DI SINI ---
        'user_id',      // <--- INI WAJIB ADA!
        'karyawan_id',
        // ------------------------------------
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'metode',
        'latitude',
        'longitude',
        'foto_masuk',
        'foto_pulang',
        'keterangan'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
        return $this->belongsTo(User::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}