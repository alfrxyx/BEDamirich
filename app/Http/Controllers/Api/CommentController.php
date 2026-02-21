<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    // LIHAT SEMUA KOMENTAR DI TASK
    public function index($task_id)
    {
        $comments = Comment::with('user')
                          ->where('task_id', $task_id)
                          ->latest()
                          ->get();

        return response()->json([
            'message' => 'Daftar komentar',
            'data' => $comments
        ]);
    }

    // KIRIM KOMENTAR BARU
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,task_id',
            'body'    => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment = Comment::create([
            'task_id' => $request->task_id,
            'user_id' => Auth::id(),
            'body'    => $request->body
        ]);

        return response()->json([
            'message' => 'Komentar terkirim!',
            'data'    => $comment->load('user')
        ], 201);
    }

    // HAPUS KOMENTAR (Cuma yang bikin yg boleh hapus)
    public function destroy($id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Komentar tidak ditemukan'], 404);
        }

        // Cek apakah yang mau hapus adalah pemilik komentar?
        if ($comment->user_id != Auth::id()) {
            return response()->json(['message' => 'Anda tidak berhak menghapus komentar ini'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Komentar dihapus']);
    }
}