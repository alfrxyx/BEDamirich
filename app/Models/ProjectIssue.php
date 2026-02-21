<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIssue extends Model
{
    protected $primaryKey = 'issue_id';
    protected $fillable = [
        'project_id',
        'task_id',
        'title',
        'description',
        'severity',
        'status',
        'reported_by'
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'id');
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