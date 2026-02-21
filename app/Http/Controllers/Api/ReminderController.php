<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Reminder;
use Carbon\Carbon;

class ReminderController extends Controller
{
    /**
     * Jalankan reminder harian untuk task deadline besok
     */
    public function runReminders()
    {
        $reminderCount = 0;
        $tomorrow = Carbon::tomorrow()->toDateString();

        // Ambil semua task yang deadline besok
        $tasksDue = Task::whereDate('due_date', $tomorrow)->get();

        foreach ($tasksDue as $task) {
            // Dapatkan assignee dari task
            $assignees = $task->assignees;

            if ($assignees->isNotEmpty()) {
                foreach ($assignees as $user) {
                    // Cek apakah reminder sudah pernah dikirim hari ini
                    $existing = Reminder::where('task_id', $task->task_id)
                                      ->where('user_id', $user->id)
                                      ->whereDate('created_at', Carbon::today())
                                      ->exists();

                    if (!$existing) {
                        Reminder::create([
                            'task_id' => $task->task_id,
                            'user_id' => $user->id,
                            'type' => 'TASK_DUE',
                            'message' => "🔔 Pengingat: Task '{$task->judul}' deadline besok!",
                            'triggered_at' => now()
                        ]);
                        $reminderCount++;
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Reminder harian selesai diproses',
            'reminders_created' => $reminderCount
        ]);
    }

    /**
     * Endpoint untuk uji coba manual (development only)
     */
    public function testReminders()
    {
        if (app()->environment() !== 'local') {
            return response()->json(['error' => 'Hanya untuk development'], 403);
        }
        return $this->runReminders();
    }
}