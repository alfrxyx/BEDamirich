<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $primaryKey = 'task_id';
    public $timestamps = true;

    protected $fillable = [
        'board_id',
        'milestone_id', // ✅ WAJIB ADA: Agar task bisa masuk ke milestone
        'judul',
        'deskripsi',
        'link_url',
        'due_date',
        'prioritas',
        'status',
        'progress_percentage',       // ✅ WAJIB ADA: Agar status sinkron saat drag & drop
        'parent_task_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'progress_percentage' => 'integer' // Casting ke integer
    ];

    // Relasi ke Board
    public function board()
    {
        return $this->belongsTo(Board::class, 'board_id', 'board_id');
    }

    // ✅ Relasi ke Milestone (PENTING untuk menghitung progress)
    public function milestone()
    {
        return $this->belongsTo(Milestone::class, 'milestone_id', 'milestone_id');
    }

    // Relasi ke Project (via Board)
    public function project()
    {
        return $this->hasOneThrough(
            Project::class,
            Board::class,
            'board_id', 
            'project_id', 
            'board_id', 
            'project_id'
        );
    }

    // Relasi ke Assignees
    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_assignees', 'task_id', 'user_id');
    }

    // Relasi ke Parent Task
    public function parentTask()
    {
        return $this->belongsTo(Task::class, 'parent_task_id', 'task_id');
    }

    // Relasi ke Sub-Tasks
    public function subTasks()
    {
        return $this->hasMany(Task::class, 'parent_task_id', 'task_id');
    }

    // Relasi ke Checklist
    public function checklists()
    {
        return $this->hasMany(Checklist::class, 'task_id', 'task_id');
    }

    // Relasi ke Komentar
    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id', 'task_id');
    }

    // Relasi ke Lampiran
    public function attachments()
    {
        return $this->hasMany(TaskAttachment::class, 'task_id', 'task_id');
    }
}