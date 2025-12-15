<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\User;

class LeaveRequestController extends Controller
{
    /**
     * Menampilkan semua permohonan cuti milik user yang sedang login.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        \Log::info('Leave Request Index - User ID', ['user_id' => $user->id]);

        $leaveRequests = LeaveRequest::where('user_id', $user->id)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        // Tambahkan kolom duration_days
        $leaveRequests->transform(function ($item) {
            $startDate = Carbon::parse($item->start_date);
            $endDate = Carbon::parse($item->end_date);
            $item->duration_days = $startDate->diffInDays($endDate) + 1; // +1 karena inklusif
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $leaveRequests,
        ], 200);
    }

    /**
     * Mengajukan permohonan cuti baru.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            \Log::error('Unauthorized: No user found in store.');
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        \Log::info('Leave Request Store - User Info', [
            'user_id' => $user->id,
            'user_name' => $user->name ?? 'unknown',
            'request_data' => $request->all()
        ]);

        $request->validate([
            'start_date' => 'required|date', // Hapus after_or_equal:today sementara untuk debugging
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|string', // Validasi in:.. dihapus sementara biar fleksibel
            'reason'     => 'required|string|max:1000',
        ]);

        try {
            // --- PERBAIKAN UTAMA: FORMAT TANGGAL ---
            // Mengubah format dari MM/DD/YYYY (default frontend) ke YYYY-MM-DD (Wajib MySQL)
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate   = Carbon::parse($request->end_date)->format('Y-m-d');

            // Cek apakah ada permohonan yang tumpang tindih
            $existingRequest = LeaveRequest::where('user_id', $user->id)
                                           ->where('status', 'pending')
                                           ->where(function ($query) use ($startDate, $endDate) {
                                               $query->whereBetween('start_date', [$startDate, $endDate])
                                                     ->orWhereBetween('end_date', [$startDate, $endDate]);
                                           })
                                           ->first();

            if ($existingRequest) {
                return response()->json(['message' => 'Anda sudah memiliki permohonan cuti yang aktif di rentang tanggal tersebut.'], 400);
            }

            \Log::info('Leave Request Store - About to Create', [
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $request->type,
                'reason' => $request->reason,
            ]);

            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'start_date' => $startDate, // Pakai variabel yang sudah diformat
                'end_date' => $endDate,     // Pakai variabel yang sudah diformat
                'type' => $request->type,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            $admins = User::where('posisi_id', 1)->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Pengajuan Cuti Baru',
                    'message' => $user->name . ' mengajukan cuti dari ' . $startDate . ' hingga ' . $endDate . '.',
                    'type' => 'leave_request',
                    'is_read' => false,
                ]);
            }
            

            \Log::info('Leave Request Store - Success', ['leave_request_id' => $leaveRequest->id]);

            return response()->json([
                'message' => 'Permohonan cuti berhasil diajukan.',
                'data' => $leaveRequest
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating leave request: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => $user->id ?? 'null',
                'trace' => $e->getTraceAsString(),
            ]);
            
            // --- PERBAIKAN: TAMPILKAN ERROR ASLI ---
            return response()->json([
                'message' => 'Gagal mengajukan permohonan cuti.',
                'debug_error' => $e->getMessage(), // Ini akan muncul di Preview browser kamu
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
 * Mendapatkan daftar notifikasi untuk user yang sedang login
 */
public function getNotifications()
{
    $user = Auth::user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    // Ambil notifikasi yang belum dibaca dan sudah dibaca, urutkan berdasarkan waktu terbaru
    $notifications = Notification::where('user_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->get();

    // Format ulang agar lebih rapi
    $formatted = $notifications->map(function ($notif) {
        return [
            'id' => $notif->id,
            'title' => $notif->title,
            'message' => $notif->message,
            'type' => $notif->type,
            'is_read' => $notif->is_read,
            'created_at' => $notif->created_at->diffForHumans(), // contoh: "2 jam lalu"
            'read_at' => $notif->read_at ? $notif->read_at->format('d/m/Y H:i') : null,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $formatted,
        'total_unread' => $notifications->where('is_read', false)->count(),
    ]);
}

    /**
     * Membatalkan permohonan cuti (hanya jika statusnya pending).
     */
    public function destroy($id)
    {   
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        try {
            $leaveRequest = LeaveRequest::where('id', $id)
                                       ->where('user_id', $user->id)
                                       ->where('status', 'pending')
                                       ->first();

            if (!$leaveRequest) {
                return response()->json(['message' => 'Permohonan cuti tidak ditemukan atau tidak dapat dibatalkan.'], 404);
            }

            $leaveRequest->delete();

            return response()->json(['message' => 'Permohonan cuti berhasil dibatalkan.'], 200);

        } catch (\Exception $e) {
            \Log::error('Error deleting leave request: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membatalkan permohonan cuti.'], 500);
        }
    }
}