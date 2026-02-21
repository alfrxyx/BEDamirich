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

        $leaveRequests = LeaveRequest::where('user_id', $user->id)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        // Tambahkan durasi hari
        $leaveRequests->transform(function ($item) {
            $startDate = Carbon::parse($item->start_date);
            $endDate = Carbon::parse($item->end_date);
            $item->duration_days = $startDate->diffInDays($endDate) + 1; // inklusif
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $leaveRequests,
        ]);
    }

    /**
     * Mengajukan permohonan cuti baru.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|in:sick,annual,unpaid,other',
            'reason'     => 'required|string|max:1000',
        ]);

        try {
            // Format tanggal ke YYYY-MM-DD (MySQL compatible)
            $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate   = Carbon::parse($request->end_date)->format('Y-m-d');

            // Cek tumpang tindih dengan cuti pending
            $existingRequest = LeaveRequest::where('user_id', $user->id)
                                           ->where('status', 'pending')
                                           ->where(function ($query) use ($startDate, $endDate) {
                                               $query->whereBetween('start_date', [$startDate, $endDate])
                                                     ->orWhereBetween('end_date', [$startDate, $endDate])
                                                     ->orWhere(function ($q) use ($startDate, $endDate) {
                                                         $q->where('start_date', '<=', $startDate)
                                                           ->where('end_date', '>=', $endDate);
                                                     });
                                           })
                                           ->first();

            if ($existingRequest) {
                return response()->json([
                    'message' => 'Anda sudah memiliki permohonan cuti yang aktif di rentang tanggal tersebut.'
                ], 400);
            }

            // Simpan pengajuan cuti
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $request->type,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            // ✅ KIRIM NOTIFIKASI KE SEMUA ADMIN (Manager & Partner)
            $admins = User::whereIn('level_position', ['Manager', 'Partner'])->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Pengajuan Cuti Baru',
                    'message' => "{$user->name} mengajukan cuti dari {$startDate} hingga {$endDate}. Alasan: {$request->reason}",
                    'type' => 'leave_request',
                    'is_read' => false,
                ]);
            }

            return response()->json([
                'message' => 'Permohonan cuti berhasil diajukan.',
                'data' => $leaveRequest
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating leave request: ' . $e->getMessage(), [
                'user_id' => $user?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal mengajukan permohonan cuti.',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mendapatkan notifikasi untuk user yang sedang login.
     */
    public function getNotifications()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $notifications = Notification::where('user_id', $user->id)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        $formatted = $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'title' => $notif->title,
                'message' => $notif->message,
                'type' => $notif->type,
                'is_read' => $notif->is_read,
                'created_at' => $notif->created_at->diffForHumans(),
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

        $leaveRequest = LeaveRequest::where('id', $id)
                                   ->where('user_id', $user->id)
                                   ->where('status', 'pending')
                                   ->first();

        if (!$leaveRequest) {
            return response()->json([
                'message' => 'Permohonan cuti tidak ditemukan atau tidak dapat dibatalkan.'
            ], 404);
        }

        $leaveRequest->delete();

        return response()->json([
            'message' => 'Permohonan cuti berhasil dibatalkan.'
        ]);
    }
}