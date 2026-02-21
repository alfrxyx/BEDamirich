<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaskAttachment;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskAttachmentController extends Controller
{
    // POST /api/attachments
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,task_id',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,xlsx,xls,doc,docx,zip,rar|max:10240'
        ], [
            'file.mimes' => 'File harus berupa gambar, PDF, Excel, Word, atau ZIP/RAR',
            'file.max' => 'Ukuran file maksimal 10MB'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = Task::find($request->task_id);
        if (!$task) {
            return response()->json(['message' => 'Task tidak ditemukan'], 404);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('attachments', time() . '_' . $originalName, 'public');

            $attachment = TaskAttachment::create([
                'task_id' => $request->task_id,
                'file_name' => $originalName,
                'file_path' => '/storage/' . $path,
                'file_type' => $file->getClientMimeType(),
                'uploaded_by' => $request->user() ? $request->user()->name : 'Unknown'
            ]);

            return response()->json([
                'message' => 'File berhasil diupload!',
                'data' => $attachment
            ], 201);
        }

        return response()->json(['message' => 'File gagal diupload'], 500);
    }

    // GET /api/tasks/{task_id}/attachments
    public function index($task_id)
    {
        $task = Task::find($task_id);
        if (!$task) {
            return response()->json(['message' => 'Task tidak ditemukan'], 404);
        }

        $attachments = TaskAttachment::where('task_id', $task_id)->get();
        return response()->json([
            'message' => 'Daftar lampiran',
            'data' => $attachments
        ]);
    }

    // DELETE /api/attachments/{id}
    public function destroy($id)
    {
        $attachment = TaskAttachment::find($id);
        if (!$attachment) {
            return response()->json(['message' => 'File tidak ditemukan'], 404);
        }

        // Hapus file fisik
        $realPath = str_replace('/storage/', '', $attachment->file_path);
        if (Storage::disk('public')->exists($realPath)) {
            Storage::disk('public')->delete($realPath);
        }

        $attachment->delete();
        return response()->json(['message' => 'File berhasil dihapus']);
    }
}