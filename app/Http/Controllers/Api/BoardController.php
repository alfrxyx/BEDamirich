<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;

class BoardController extends Controller
{
    // 1. LIHAT SEMUA BOARD DI SUATU PROJECT
    public function index($project_id)
    {
        $project = Project::find($project_id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $boards = Board::where('project_id', $project_id)
                      ->with('tasks')
                      ->orderBy('board_id')
                      ->get();

        return response()->json([
            'message' => 'List Board dalam Project',
            'data' => $boards
        ]);
    }

    // 2. BUAT BOARD BARU
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,project_id',
            'nama_board' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cek apakah project milik user (opsional)
        $project = Project::find($request->project_id);
        if (!$project) {
            return response()->json(['message' => 'Project tidak valid'], 400);
        }

        $board = Board::create([
            'project_id' => $request->project_id,
            'nama_board' => $request->nama_board,
            'nama_status' => $request->nama_board // Sesuai struktur database kamu
        ]);

        return response()->json([
            'message' => 'Board berhasil dibuat',
            'data' => $board
        ], 201);
    }

    // 3. UPDATE BOARD
    public function update(Request $request, $id)
    {
        $board = Board::find($id);
        if (!$board) {
            return response()->json(['message' => 'Board tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_board' => 'sometimes|required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $board->update([
            'nama_board' => $request->nama_board,
            'nama_status' => $request->nama_board // Sinkronisasi nama_status
        ]);

        return response()->json([
            'message' => 'Board berhasil diupdate',
            'data' => $board
        ]);
    }

    // 4. HAPUS BOARD
    public function destroy($id)
    {
        $board = Board::find($id);
        if (!$board) {
            return response()->json(['message' => 'Board tidak ditemukan'], 404);
        }

        $board->delete();
        return response()->json(['message' => 'Board berhasil dihapus']);
    }
}