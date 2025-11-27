<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawans';

    protected $fillable = [
        'user_id',
        'divisi_id',
        'posisi_id',
        'nama',
        'email',
        'password',
        'status_kerja',
        'tanggal_masuk',
        'foto_profil',
        'sisa_cuti'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function posisi()
    {
        return $this->belongsTo(Posisi::class);
    }
}