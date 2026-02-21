<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BugReport extends Model
{
    protected $primaryKey = 'bug_id';
    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'title',
        'description',
        'priority',
        'severity',
        'status'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }
}