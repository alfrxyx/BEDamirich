<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Handle upload dokumen oleh user.
     */
    public function uploadDocument(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:KTP,NPWP,BPJS',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // max 5MB
        ], [
            'document_type.required' => 'Jenis dokumen wajib dipilih.',
            'document_type.in' => 'Jenis dokumen tidak valid.',
            'file.required' => 'File dokumen wajib diupload.',
            'file.mimes' => 'File harus berupa PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $documentType = $request->input('document_type');
            $file = $request->file('file');

            \Log::info('Mulai upload dokumen', [
                'user_id' => Auth::id(),
                'document_type' => $documentType,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            // Generate nama file unik
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Simpan file ke storage
            $filePath = Storage::disk('public')->putFileAs(
                'uploads/documents',
                $file,
                $fileName
            );

            \Log::info('File disimpan', ['file_path' => $filePath]);

            // ✅ Tambahkan baris ini!
            $userId = Auth::id();

            // Simpan ke database
            DB::table('documents')->insert([
                'user_id' => $userId,
                'document_type' => $documentType,
                'file_path' => $filePath,
                'uploaded_at' => now(),
            ]);

            \Log::info('Dokumen disimpan ke database');

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload.',
                'data' => [
                    'document_type' => $documentType,
                    'file_path' => $filePath,
                    'uploaded_at' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Upload Document Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengupload dokumen. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Admin: Ambil semua dokumen yang diupload user.
     */
    public function index()
    {
        try {
            $documents = DB::table('documents')
                ->join('users', 'documents.user_id', '=', 'users.id')
                ->select(
                    'documents.id',
                    'users.name as user_name',
                    'documents.document_type',
                    'documents.file_path',
                    'documents.uploaded_at'
                )
                ->orderBy('documents.uploaded_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $documents,
            ]);
        } catch (\Exception $e) {
            \Log::error('Fetch Documents Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data dokumen.',
            ], 500);
        }
    }
}