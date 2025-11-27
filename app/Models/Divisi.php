<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisis';
    
    // Lindungi id, sisanya boleh diisi massal
    protected $guarded = ['id'];

    // Relasi: Satu Divisi punya banyak Karyawan
    public function karyawans()
    {
        return $this->hasMany(Karyawan::class);
    }
}