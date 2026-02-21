<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskAttachment extends Model
{
    protected $table = 'task_attachments'; // Biasanya nama tabel plural
    protected $primaryKey = 'attachment_id'; 
    public $timestamps = true;

    protected $fillable = [
        'task_id',
        'file_name',
        'file_path',
        'uploaded_at'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id', 'task_id');
    }
}