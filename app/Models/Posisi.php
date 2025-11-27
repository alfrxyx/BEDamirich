<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Posisi extends Model
{
    use HasFactory;

    protected $table = 'posisis';
    
    protected $guarded = ['id'];

    // Relasi: Satu Posisi punya banyak Karyawan
    public function karyawans()
    {
        return $this->hasMany(Karyawan::class);
    }
}