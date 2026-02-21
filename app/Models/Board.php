<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;

    protected $primaryKey = 'board_id';
    public $timestamps = true;

    // ✅ Pastikan project_id ada disini agar bisa diisi lewat create()
    protected $fillable = [
        'project_id',
        'nama_board'
    ];

    public function project()
    {
        // Parameter: (Model, Foreign Key di tabel boards, Owner Key di tabel projects)
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'board_id', 'board_id');
    }
}