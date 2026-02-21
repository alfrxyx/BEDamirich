<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    protected $fillable = ['task_id', 'title', 'is_completed'];

    protected $casts = [
        'is_completed' => 'boolean'
    ];
}