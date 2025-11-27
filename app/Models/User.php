<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'divisi_id',
        'posisi_id',
        'tanggal_masuk',
        'attendance_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // User milik satu Divisi
    
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    // User punya satu Posisi
    public function posisi()
    {
        return $this->belongsTo(Posisi::class, 'posisi_id');
    }

    // Relasi PENTING agar AdminController bisa ambil data karyawan
    public function karyawan()
    {
        return $this->hasOne(Karyawan::class, 'user_id', 'id');
    }
}