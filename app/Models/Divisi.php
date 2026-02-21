<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    use HasFactory;

    // ✅ PERBAIKAN: Ubah nama tabel menjadi 'divisi' (tanpa 's')
    protected $table = 'divisi';
    
    // ✅ PERBAIKAN: Gunakan $fillable untuk keamanan
    protected $fillable = ['name'];

    // ✅ PERBAIKAN: Relasi ke User (bukan Karyawan)
    public function users()
    {
        return $this->hasMany(User::class, 'divisi_id');
    }
}