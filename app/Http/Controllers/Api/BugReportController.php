<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BugReport;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class BugReportController extends Controller
{
    /**
     * Lihat semua bug di project tertentu
     */
    public function index($project_id)
    {
        $project = Project::find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $bugs = BugReport::with(['reporter:id,name,email', 'task:task_id,judul'])
                        ->where('project_id', $project_id)
                        ->latest()
                        ->get();

        return response()->json([
            'message' => 'Daftar bug berhasil diambil',
            'data' => $bugs
        ]);
    }

    /**
     * Laporkan bug baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,project_id',
            'task_id' => 'nullable|exists:tasks,task_id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Critical',
            'severity' => 'required|in:Minor,Major,Critical'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bug = BugReport::create([
            'project_id' => $request->project_id,
            'task_id' => $request->task_id,
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'severity' => $request->severity,
            'status' => 'Open'
        ]);

        return response()->json([
            'message' => 'Bug berhasil dilaporkan!',
            'data' => $bug
        ], 201);
    }

    /**
     * Update status bug
     */
    public function update(Request $request, $id)
    {
        $bug = BugReport::find($id);
        if (!$bug) {
            return response()->json(['message' => 'Bug tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:Open,In Progress,Resolved,Closed',
            'priority' => 'sometimes|in:Low,Medium,High,Critical'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $bug->update($request->only(['status', 'priority']));

        return response()->json([
            'message' => 'Status bug berhasil diperbarui',
            'data' => $bug
        ]);
    }

    /**
     * Hapus bug report
     */
    public function destroy($id)
    {
        $bug = BugReport::find($id);
        if (!$bug) {
            return response()->json(['message' => 'Bug tidak ditemukan'], 404);
        }

        // Hanya pemilik atau admin yang bisa hapus
        if ($bug->user_id !== auth()->id() && auth()->user()->level_position !== 'Partner') {
            return response()->json(['message' => 'Anda tidak berhak menghapus laporan ini'], 403);
        }

        $bug->delete();
        return response()->json(['message' => 'Laporan bug berhasil dihapus']);
    }
}