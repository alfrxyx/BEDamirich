<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Tambahkan ini best practice
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    // ❌ SALAH (Jika database kamu pakai project_id)
    // protected $primaryKey = 'id'; 

    // ✅ BENAR (Sesuaikan dengan nama kolom di database)
    protected $primaryKey = 'project_id';

    protected $fillable = [
        'nama_project',
        'deskripsi',
        'divisi',
        'created_by',
        'project_manager_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'status'
    ];

    public function boards()
    {
        // Parameter: (Model, Foreign Key di tabel boards, Local Key di tabel projects)
        return $this->hasMany(Board::class, 'project_id', 'project_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'project_manager_id', 'id');
    }
}