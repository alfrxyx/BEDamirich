<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Login & Identitas
        'name',
        'email',
        'password',
        'whatsapp',
        'company_name',
        'company_type',
        'user_id_code', // ✅ SUDAH ADA
        
        // Pekerjaan
        'divisi_id',
        'posisi_id',
        'level_position', // ✅ Tambahkan ini (sebelumnya di bagian "Field Baru", sekarang pindah ke sini biar rapi)
        'tanggal_masuk',
        'status_aktif',
        'history_mutasi',
        
        // Token & Foto
        'attendance_token',
        'foto_profil',
        
        // Alamat & Pendidikan
        'alamat_saat_ini',
        'institutional_formal_education',
        'formal_education',
        'study_program',
        'start_date_formal_education',
        'end_date_formal_education',
        
        // Pendidikan Non-Formal
        'non_formal_education',
        'types_non_formal_education',
        'program_name_non_formal',
        'institution_non_formal',
        
        // Pengalaman Kerja
        'working_experience',
        'company_working_experience',
        'job_position_working_experience',
        'job_responsibilities',
        
        // Media Sosial
        'social_media',
        'url_social_media',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tanggal_masuk' => 'date',
        'start_date_formal_education' => 'date',
        'end_date_formal_education' => 'date',
    ];

    // Relasi ke Divisi
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    // Relasi ke Posisi
    public function posisi()
    {
        return $this->belongsTo(Posisi::class, 'posisi_id');
    }

    // Accessor untuk URL foto profil
    protected $appends = ['foto_profil_url'];

    public function getFotoProfilUrlAttribute()
    {
        if ($this->foto_profil) {
            return asset('storage/' . $this->foto_profil);
        }
        return null;
    }
}