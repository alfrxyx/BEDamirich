<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ambil SEMUA task yang berada di board Notion-style
        $allTasks = Task::with([
            'board.project',
            'assignees:id,name,email',
            'checklists',
            'comments.user:id,name,email',
            'attachments'
        ])
        ->whereHas('board', function ($q) {
            $q->whereIn('nama_board', ['To Do', 'Doing', 'Done']);
        })
        ->get();

        // Kelompokkan berdasarkan nama board
        $not_started = $allTasks->filter(fn($t) => $t->board->nama_board === 'To Do')->values();
        $in_progress = $allTasks->filter(fn($t) => $t->board->nama_board === 'Doing')->values();
        $done        = $allTasks->filter(fn($t) => $t->board->nama_board === 'Done')->values();

        // Hitung statistik
        $today = Carbon::today();
        $total_overdue = $allTasks->filter(fn($t) =>
            $t->board->nama_board !== 'Done' &&
            Carbon::parse($t->due_date)->lt($today)
        )->count();

        $total_today = $allTasks->filter(fn($t) =>
            Carbon::parse($t->due_date)->isSameDay($today)
        )->count();

        return response()->json([
            'message' => 'Dashboard Data Loaded',
            'summary' => [
                'total_overdue'   => $total_overdue,
                'total_today'     => $total_today,
                'total_weekly'    => $done->count(),
                'my_active_tasks' => $not_started->count() + $in_progress->count()
            ],
            'lists' => [
                'not_started' => $not_started,
                'in_progress' => $in_progress,
                'done'        => $done,
                // Kompatibilitas
                'overdue' => $not_started,
                'today'   => $in_progress,
                'weekly'  => $done
            ]
        ]);
    }
}