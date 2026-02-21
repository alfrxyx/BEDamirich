<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Board;
use App\Models\Checklist;
use App\Models\TaskLog; 
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['assignees:id,name,email', 'board.project']);

        if ($request->has('board_id')) {
            $query->where('board_id', $request->board_id);
        }

        $tasks = $query->latest()->get();

        return response()->json([
            'message' => 'Daftar Task',
            'data' => $tasks
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'board_id'     => 'required|exists:boards,board_id',
            'judul'        => 'required|string|max:255',
            'prioritas'    => 'required|in:Low,Medium,High,Urgent',
            'due_date'     => 'required|date',
            'status'       => 'nullable|string',
            'assignee_id'  => 'nullable|exists:users,id',
            'milestone_id' => 'nullable|exists:milestones,milestone_id',
            'link_url'     => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task = Task::create([
            'board_id'            => $request->board_id,
            'milestone_id'        => $request->milestone_id,
            'judul'               => $request->judul,
            'deskripsi'           => $request->description ?? $request->deskripsi ?? null,
            'link_url'            => $request->link_url,
            'prioritas'           => $request->prioritas,
            'due_date'            => $request->due_date,
            'status'              => $request->status ?? 'not_started',
            'progress_percentage' => 0
        ]);

        if ($request->filled('assignee_id')) {
            $task->assignees()->attach($request->assignee_id);
        } elseif ($request->user()) {
            $task->assignees()->attach($request->user()->id);
        }

        // CATAT ACTIVITY LOG (CREATE)
        TaskLog::create([
            'task_id' => $task->task_id,
            'user_id' => $request->user() ? $request->user()->id : null,
            'action' => 'create',
            'description' => 'Membuat task baru'
        ]);

        return response()->json([
            'message' => 'Task berhasil dibuat!',
            'data' => $task->load('assignees:id,name,email')
        ], 201);
    }

    public function show($id)
    {
        $task = Task::with(['assignees:id,name,email', 'board.project', 'checklists', 'comments.user', 'attachments'])->find($id);
        if (!$task) return response()->json(['message' => 'Task tidak ditemukan'], 404);
        return response()->json(['data' => $task]);
    }

    // UPDATE: Logic Otomatisasi Status, Progress, & Komprehensif Activity Log
    public function update(Request $request, $id)
    {
        $task = Task::find($id);
        if (!$task) return response()->json(['message' => 'Task tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), [
            'judul'               => 'sometimes|required|string|max:255',
            'prioritas'           => 'sometimes|required|in:Low,Medium,High,Urgent',
            'due_date'            => 'sometimes|required|date',
            'status'              => 'sometimes|string',
            'board_id'            => 'sometimes|exists:boards,board_id',
            'assignee_id'         => 'nullable|exists:users,id',
            'milestone_id'        => 'nullable|exists:milestones,milestone_id',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'link_url'            => 'nullable|string'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $dataToUpdate = $request->only(['judul', 'prioritas', 'due_date', 'status', 'board_id', 'milestone_id', 'progress_percentage', 'link_url']);

        if ($request->has('description') || $request->has('deskripsi')) {
            $dataToUpdate['deskripsi'] = $request->description ?? $request->deskripsi;
        }

        // ==========================================
        // 1. SIMPAN DATA LAMA UNTUK CEK PERUBAHAN LOG
        // ==========================================
        $oldData = [
            'status' => $task->status,
            'progress' => $task->progress_percentage,
            'judul' => $task->judul,
            'deskripsi' => $task->deskripsi,
            'due_date' => $task->due_date, 
            'link_url' => $task->link_url
        ];

        // ==========================================
        // 2. LOGIKA OTOMATISASI PROGRESS & STATUS
        // ==========================================
        if ($request->has('progress_percentage') && intval($request->progress_percentage) == 100) {
            $dataToUpdate['status'] = 'done';
            $doneBoard = Board::where('project_id', $task->board->project_id)->where('nama_board', 'Done')->first();
            if ($doneBoard) $dataToUpdate['board_id'] = $doneBoard->board_id;
        }
        
        if ($request->has('status') && $request->status == 'done') {
            $dataToUpdate['progress_percentage'] = 100;
        }

        if ($request->has('board_id')) {
            $newBoard = Board::find($request->board_id);
            if ($newBoard) {
                $newStatus = match($newBoard->nama_board) { 'To Do' => 'not_started', 'Doing' => 'in_progress', 'Done' => 'done', default => 'not_started' };
                $dataToUpdate['status'] = $newStatus;
                if ($newStatus == 'done') $dataToUpdate['progress_percentage'] = 100;
            }
        }

        if ($request->has('status') && !$request->has('board_id')) {
            $currentBoard = $task->board; 
            if ($currentBoard) {
                $targetBoardName = match($request->status) { 'not_started' => 'To Do', 'in_progress' => 'Doing', 'done' => 'Done', default => null };
                if ($targetBoardName) {
                    $targetBoard = Board::where('project_id', $currentBoard->project_id)->where('nama_board', $targetBoardName)->first();
                    if ($targetBoard) $dataToUpdate['board_id'] = $targetBoard->board_id;
                }
            }
        }

        // ==========================================
        // 3. UPDATE TASK KE DATABASE
        // ==========================================
        $task->update($dataToUpdate);

        if ($request->has('assignee_id')) {
            $task->assignees()->sync([$request->assignee_id]);
        }

        // ==========================================
        // 4. CATAT ACTIVITY LOG (DETEKSI PERUBAHAN)
        // ==========================================
        $user = $request->user();
        $userId = $user ? $user->id : null;
        $changes = [];

        if (isset($dataToUpdate['status']) && $oldData['status'] !== $dataToUpdate['status']) {
            $statusName = strtoupper(str_replace('_', ' ', $dataToUpdate['status']));
            $changes[] = "Mengubah status menjadi {$statusName}";
        }
        
        if (isset($dataToUpdate['progress_percentage']) && $oldData['progress'] != $dataToUpdate['progress_percentage']) {
            $changes[] = "Memperbarui progress menjadi " . $dataToUpdate['progress_percentage'] . "%";
        }

        if (array_key_exists('judul', $dataToUpdate)) {
            $oldTitle = trim((string) $oldData['judul']);
            $newTitle = trim((string) $dataToUpdate['judul']);
            if ($oldTitle !== $newTitle) $changes[] = "Mengubah judul task";
        }

        if (array_key_exists('deskripsi', $dataToUpdate)) {
            $oldDesc = trim((string) $oldData['deskripsi']);
            $newDesc = trim((string) $dataToUpdate['deskripsi']);
            if ($oldDesc !== $newDesc) $changes[] = "Memperbarui deskripsi task";
        }

        if (array_key_exists('due_date', $dataToUpdate)) {
            $oldDateStr = $oldData['due_date'] ? \Carbon\Carbon::parse($oldData['due_date'])->format('Y-m-d') : null;
            $newDateStr = $dataToUpdate['due_date'] ? \Carbon\Carbon::parse($dataToUpdate['due_date'])->format('Y-m-d') : null;

            if ($oldDateStr !== $newDateStr) {
                $changes[] = "Mengubah tenggat waktu menjadi " . $newDateStr;
            }
        }

        if (array_key_exists('link_url', $dataToUpdate)) {
            $oldLink = trim((string) $oldData['link_url']);
            $newLink = trim((string) $dataToUpdate['link_url']);
            
            if ($oldLink !== $newLink) {
                if (empty($oldLink) && !empty($newLink)) $changes[] = "Menambahkan link lampiran";
                elseif (!empty($oldLink) && empty($newLink)) $changes[] = "Menghapus link lampiran";
                else $changes[] = "Memperbarui link lampiran";
            }
        }

        if (count($changes) > 0) {
            $description = implode(", ", $changes);
            TaskLog::create([
                'task_id' => $task->task_id, 
                'user_id' => $userId, 
                'action' => 'update',
                'description' => $description
            ]);
        }

        return response()->json([
            'message' => 'Task berhasil diupdate',
            'data' => $task->load('assignees:id,name,email')
        ]);
    }

    public function destroy($id)
    {
        $task = Task::find($id);
        if (!$task) return response()->json(['message' => 'Task tidak ditemukan'], 404);
        
        $task->delete();
        return response()->json(['message' => 'Task berhasil dihapus']);
    }

    public function getChecklists($id) {
        $task = Task::with('checklists')->find($id);
        return response()->json(['data' => $task ? $task->checklists : []]);
    }
    public function getComments($id) {
        $task = Task::with('comments.user')->find($id);
        return response()->json(['data' => $task ? $task->comments : []]);
    }
    public function getAttachments($id) {
        $task = Task::with('attachments')->find($id);
        return response()->json(['data' => $task ? $task->attachments : []]);
    }

    // ==========================================
    // CHECKLIST API & AUTO-PROGRESS
    // ==========================================

    // ✅ FUNGSI HELPER: Kalkulasi ulang progress setiap ada perubahan checklist
    private function syncTaskProgress($taskId) {
        $task = Task::find($taskId);
        if (!$task) return;

        $totalChecklists = Checklist::where('task_id', $taskId)->count();
        
        if ($totalChecklists > 0) {
            $completedChecklists = Checklist::where('task_id', $taskId)->where('is_completed', true)->count();
            $percentage = round(($completedChecklists / $totalChecklists) * 100);
            
            $dataToUpdate = ['progress_percentage' => $percentage];

            // Pindah otomatis jika 100%
            if ($percentage == 100) {
                $dataToUpdate['status'] = 'done';
                $doneBoard = Board::where('project_id', $task->board->project_id)->where('nama_board', 'Done')->first();
                if ($doneBoard) $dataToUpdate['board_id'] = $doneBoard->board_id;
            } 
            // Pindah otomatis ke In Progress jika baru dicentang 1 tapi statusnya masih To Do
            elseif ($percentage > 0 && $task->status == 'not_started') {
                $dataToUpdate['status'] = 'in_progress';
                $doingBoard = Board::where('project_id', $task->board->project_id)->where('nama_board', 'Doing')->first();
                if ($doingBoard) $dataToUpdate['board_id'] = $doingBoard->board_id;
            }

            $task->update($dataToUpdate);
        } else {
            // Jika semua checklist dihapus, reset ke 0 jika status masih Not Started
            if ($task->status == 'not_started') {
                 $task->update(['progress_percentage' => 0]);
            }
        }
    }

    public function addChecklist(Request $request, $taskId) {
        $request->validate(['title' => 'required|string|max:255']);
        $checklist = Checklist::create(['task_id' => $taskId, 'title' => $request->title, 'is_completed' => false]);
        
        // Panggil auto-update progress
        $this->syncTaskProgress($taskId);

        return response()->json(['message' => 'Checklist ditambah', 'data' => $checklist]);
    }

    public function updateChecklist(Request $request, $checklistId) {
        $checklist = Checklist::find($checklistId);
        if(!$checklist) return response()->json(['message' => 'Not found'], 404);
        
        $checklist->update(['is_completed' => $request->is_completed]);
        
        // Panggil auto-update progress
        $this->syncTaskProgress($checklist->task_id);

        return response()->json(['message' => 'Checklist diupdate', 'data' => $checklist]);
    }

    public function deleteChecklist($checklistId) {
        $checklist = Checklist::find($checklistId);
        if(!$checklist) return response()->json(['message' => 'Not found'], 404);

        $taskId = $checklist->task_id;
        $checklist->delete();
        
        // Panggil auto-update progress
        $this->syncTaskProgress($taskId);

        return response()->json(['message' => 'Checklist dihapus']);
    }

    // ==========================================
    // FITUR TAHAP 3: LOGS & EXPORT CSV
    // ==========================================
    public function getLogs($id) {
        $logs = TaskLog::with('user:id,name')->where('task_id', $id)->latest()->get();
        return response()->json(['data' => $logs]);
    }

    public function exportCsv(Request $request)
    {
        $query = Task::with(['assignees', 'board.project']);

        // Filter berdasarkan project
        if ($request->filled('project_id')) {
            $query->whereHas('board', function($q) use ($request) {
                $q->where('project_id', $request->project_id);
            });
        }

        // Filter berdasarkan User/Assignee (Bisa lebih dari 1 user)
        if ($request->filled('user_ids')) {
            $userIds = explode(',', $request->user_ids);
            $query->whereHas('assignees', function($q) use ($userIds) {
                $q->whereIn('users.id', $userIds);
            });
        }

        $tasks = $query->latest()->get();

        // Setup Header untuk Download File CSV
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Laporan_Task_" . date('Y-m-d_H-i-s') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Project', 'Judul Task', 'Prioritas', 'Status', 'Progress', 'Deadline', 'Assignee'];

        $callback = function() use($tasks, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns); // Tulis Header Tabel

            foreach ($tasks as $task) {
                $projectName = $task->board && $task->board->project ? $task->board->project->nama_project : '-';
                $assignees = $task->assignees->pluck('name')->implode(', ');
                
                fputcsv($file, [
                    $projectName,
                    $task->judul,
                    $task->prioritas,
                    $task->status,
                    $task->progress_percentage . '%',
                    $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : '-',
                    $assignees
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}