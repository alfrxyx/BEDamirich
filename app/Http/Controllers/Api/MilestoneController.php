<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class MilestoneController extends Controller
{
    /**
     * LIHAT SEMUA MILESTONE DI PROJECT
     */
    public function index($project_id)
    {
        $project = Project::find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        // ✅ FIX: Tambahkan with('tasks') agar perhitungan progress di Model bisa jalan
        $milestones = Milestone::where('project_id', $project_id)
                               ->with('tasks') // Eager Load tasks
                               ->orderBy('due_date', 'asc')
                               ->get();

        return response()->json([
            'message' => 'Daftar milestone',
            'data' => $milestones
        ]);
    }

    /**
     * BUAT MILESTONE BARU
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id'  => 'required|exists:projects,project_id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'status'      => 'nullable|in:Pending,Achieved,Delayed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $milestone = Milestone::create([
            'project_id'  => $request->project_id,
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
            'status'      => $request->status ?? 'Pending'
        ]);

        return response()->json([
            'message' => 'Milestone berhasil dibuat',
            'data' => $milestone
        ], 201);
    }

    /**
     * LIHAT DETAIL MILESTONE
     */
    public function show($id)
    {
        // Load milestone beserta task-task yang ada di dalamnya
        $milestone = Milestone::with(['project', 'tasks'])->find($id);

        if (!$milestone) {
            return response()->json(['message' => 'Milestone tidak ditemukan'], 404);
        }

        return response()->json(['data' => $milestone]);
    }

    /**
     * UPDATE MILESTONE
     */
    public function update(Request $request, $id)
    {
        $milestone = Milestone::find($id);
        if (!$milestone) {
            return response()->json(['message' => 'Milestone tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'sometimes|required|date',
            'status'      => 'sometimes|in:Pending,Achieved,Delayed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $milestone->update($request->only(['title', 'description', 'due_date', 'status']));

        // Return data fresh dengan progress terbaru
        return response()->json([
            'message' => 'Milestone berhasil diupdate',
            'data' => $milestone->load('tasks') 
        ]);
    }

    /**
     * HAPUS MILESTONE
     */
    public function destroy($id)
    {
        $milestone = Milestone::find($id);
        if (!$milestone) {
            return response()->json(['message' => 'Milestone tidak ditemukan'], 404);
        }

        $milestone->delete();
        return response()->json(['message' => 'Milestone berhasil dihapus']);
    }
}