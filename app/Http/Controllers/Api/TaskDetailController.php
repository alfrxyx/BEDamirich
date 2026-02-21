<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;

class TaskDetailController extends Controller
{
    public function checklists(Task $task)
    {
        return response()->json([
            'data' => $task->checklists
        ]);
    }

    public function comments(Task $task)
    {
        return response()->json([
            'data' => $task->comments->load('user')
        ]);
    }

    public function attachments(Task $task)
    {
        return response()->json([
            'data' => $task->attachments
        ]);
    }
}