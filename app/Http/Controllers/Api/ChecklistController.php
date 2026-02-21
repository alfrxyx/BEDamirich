<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Support\Facades\Validator;

class ChecklistController extends Controller
{
    // 1. TAMBAH CHECKLIST BARU KE TASK
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,task_id',
            'judul_checklist' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Pastikan user punya akses ke task ini (opsional tapi aman)
        $task = Task::find($request->task_id);
        if (!$task) {
            return response()->json(['message' => 'Task tidak ditemukan'], 404);
        }

        $checklist = Checklist::create([
            'task_id' => $request->task_id,
            'judul_checklist' => $request->judul_checklist,
            'is_completed' => false
        ]);

        return response()->json([
            'message' => 'Checklist berhasil dibuat',
            'data' => $checklist
        ], 201);
    }

    // 2. UPDATE CHECKLIST (Centang Selesai / Ganti Nama)
    public function update(Request $request, $id)
    {
        $checklist = Checklist::find($id);
        if (!$checklist) {
            return response()->json(['message' => 'Checklist tidak ditemukan'], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'is_completed' => 'required|boolean',
            'judul_checklist' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update data
        $checklist->update($request->only(['is_completed', 'judul_checklist']));

        // Hitung ulang progress task
        $task = $checklist->task;
        $total = $task->checklists()->count();
        $done = $task->checklists()->where('is_completed', true)->count();
        $percentage = $total > 0 ? round(($done / $total) * 100) : 0;

        // Simpan progress
        $task->progress = $percentage;
        $task->save();

        // === FITUR AUTOMATION: AUTO MOVE TO DONE ===
        if ($percentage == 100) {
            // Cari Board "Done" di project yang sama
            $doneBoard = Board::where('project_id', $task->board->project_id)
                             ->where('nama_board', 'Done')
                             ->first();

            if ($doneBoard) {
                $task->board_id = $doneBoard->board_id;
                $task->save();
            }
        }
        // ===========================================

        return response()->json([
            'message' => 'Checklist berhasil diupdate',
            'task_progress' => $percentage . '%',
            'data' => $checklist
        ]);
    }

    // 3. HAPUS CHECKLIST
    public function destroy($id)
    {
        $checklist = Checklist::find($id);
        if (!$checklist) {
            return response()->json(['message' => 'Checklist tidak ditemukan'], 404);
        }

        $checklist->delete();
        return response()->json(['message' => 'Checklist berhasil dihapus']);
    }
}