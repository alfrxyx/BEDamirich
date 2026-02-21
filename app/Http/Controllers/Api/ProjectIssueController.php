<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProjectIssue;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class ProjectIssueController extends Controller
{
    /**
     * Lihat semua issue di project tertentu
     */
    public function index($project_id)
    {
        $project = Project::find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $issues = ProjectIssue::with(['reporter:id,name,email', 'task:task_id,judul'])
                            ->where('project_id', $project_id)
                            ->latest()
                            ->get();

        return response()->json([
            'message' => 'Daftar issue berhasil diambil',
            'data' => $issues
        ]);
    }

    /**
     * Laporkan issue baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,project_id',
            'task_id' => 'nullable|exists:tasks,task_id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'required|in:Low,Medium,High,Critical'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $issue = ProjectIssue::create([
            'project_id' => $request->project_id,
            'task_id' => $request->task_id,
            'title' => $request->title,
            'description' => $request->description,
            'severity' => $request->severity,
            'status' => 'Open',
            'reported_by' => $request->user()->id
        ]);

        return response()->json([
            'message' => 'Issue berhasil dilaporkan!',
            'data' => $issue
        ], 201);
    }

    /**
     * Update status issue
     */
    public function update(Request $request, $id)
    {
        $issue = ProjectIssue::find($id);
        if (!$issue) {
            return response()->json(['message' => 'Issue tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:Open,In Progress,Resolved,Closed',
            'severity' => 'sometimes|in:Low,Medium,High,Critical',
            'description' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $issue->update($request->only(['status', 'severity', 'description']));

        return response()->json([
            'message' => 'Issue berhasil diperbarui',
            'data' => $issue
        ]);
    }

    /**
     * Hapus issue
     */
    public function destroy($id)
    {
        $issue = ProjectIssue::find($id);
        if (!$issue) {
            return response()->json(['message' => 'Issue tidak ditemukan'], 404);
        }

        // Hanya pelapor atau admin yang bisa hapus
        if ($issue->reported_by !== auth()->id() && auth()->user()->level_position !== 'Partner') {
            return response()->json(['message' => 'Anda tidak berhak menghapus issue ini'], 403);
        }

        $issue->delete();
        return response()->json(['message' => 'Issue berhasil dihapus']);
    }
}