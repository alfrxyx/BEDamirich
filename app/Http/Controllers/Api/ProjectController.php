<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    /**
     * Tampilkan semua project
     */
    public function index(Request $request)
    {
        $query = Project::query();

        // ❌ HAPUS ATAU KOMENTAR BAGIAN INI (Supaya semua user bisa lihat semua project)
        /* if ($request->user()->level_position !== 'Partner') {
            $query->where('divisi', $request->user()->divisi?->name ?? 'Development');
        }
        */

        // Search Filter (Tetap biarkan)
        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('nama_project', 'like', "%{$request->keyword}%")
                  ->orWhere('deskripsi', 'like', "%{$request->keyword}%");
            });
        }

        // Eager load manager (PIC)
        $projects = $query->with('manager:id,name')->latest()->paginate(10);

        return response()->json([
            'message' => 'Daftar Project',
            'data' => $projects
        ]);
    }

    /**
     * Ambil list user untuk dropdown Assign
     */
    public function getAssignableUsers()
    {
        $users = User::where('level_position', '!=', 'Partner')
                     ->select('id', 'name', 'level_position')
                     ->orderBy('name', 'asc')
                     ->get();

        return response()->json([
            'message' => 'List User Assignable',
            'data' => $users
        ]);
    }

    /**
     * Buat Project Baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_project'    => 'required|string|max:255',
            'deskripsi'       => 'nullable|string',
            'divisi'          => 'nullable|string',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $divisi     = $request->divisi ?? 'Development'; 
            $tglMulai   = $request->tanggal_mulai ?? now();
            $tglSelesai = $request->tanggal_selesai ?? now()->addMonth();

            $project = Project::create([
                'nama_project'       => $request->nama_project,
                'deskripsi'          => $request->deskripsi,
                'divisi'             => $divisi,
                'created_by'         => $request->user()->id,
                'project_manager_id' => $request->user()->id, // Otomatis User Login
                'tanggal_mulai'      => $tglMulai,
                'tanggal_selesai'    => $tglSelesai,
                'status'             => $request->status ?? 'Aktif'
            ]);

            // Default Board
            $defaultBoards = ['To Do', 'Doing', 'Done'];
            foreach ($defaultBoards as $boardName) {
                Board::create([
                    'project_id' => $project->project_id,
                    'nama_board' => $boardName,
                ]);
            }

            return response()->json([
                'message' => 'Project berhasil dibuat',
                'data' => $project
            ], 201);

        } catch (\Exception $e) {
            Log::error('Gagal membuat project', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);
            return response()->json(['message' => 'Terjadi kesalahan server.'], 500);
        }
    }

    /**
     * Detail Project
     */
    public function show($id)
    {
        $project = Project::with([
            'boards.tasks.assignees', // Load Task & Assignee
            'manager',                // Load PIC
            'milestones.tasks'        // ✅ OPTIMIZED: Load Milestone BESERTA Task-nya agar progress terhitung
        ])->find($id);

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        return response()->json(['data' => $project]);
    }

    /**
     * Update Project
     */
    public function update(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_project'       => 'sometimes|required|string|max:255',
            'deskripsi'          => 'nullable|string',
            'divisi'             => 'sometimes|required|string',
            'project_manager_id' => 'sometimes|exists:users,id',
            'tanggal_mulai'      => 'sometimes|required|date',
            'tanggal_selesai'    => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'status'             => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $project->update($request->only([
            'nama_project', 'deskripsi', 'divisi', 
            'project_manager_id', 
            'tanggal_mulai', 'tanggal_selesai', 'status'
        ]));

        return response()->json([
            'message' => 'Project berhasil diupdate',
            'data' => $project
        ]);
    }

    /**
     * Hapus Project
     */
    public function destroy($id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $project->delete();
        return response()->json(['message' => 'Project berhasil dihapus']);
    }
}