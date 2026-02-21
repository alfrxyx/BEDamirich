<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $primaryKey = 'milestone_id';
    
    protected $fillable = ['project_id', 'title', 'description', 'due_date', 'status'];

    protected $appends = ['progress']; 

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'milestone_id', 'milestone_id');
    }

    /**
     * ✅ LOGIKA HITUNG PROGRESS (RATA-RATA PERSENTASE)
     * Menghitung rata-rata dari field 'progress_percentage' semua task
     */
    public function getProgressAttribute()
    {
        // Pastikan tasks sudah diload
        if (!$this->relationLoaded('tasks')) {
            $this->load('tasks');
        }

        $totalTasks = $this->tasks->count();
        
        if ($totalTasks === 0) {
            return 0;
        }

        // Jumlahkan semua persentase (misal: 50 + 100 + 0 = 150)
        $sumProgress = $this->tasks->sum('progress_percentage');

        // Bagi dengan jumlah task (misal: 150 / 3 = 50%)
        return round($sumProgress / $totalTasks);
    }
}