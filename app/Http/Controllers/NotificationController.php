<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Ambil notifikasi untuk user yang sedang login.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $unreadCount = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'data' => $notifications,
                'unread_count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching notifications:', [
                'user_id' => $user->id ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $notif = Notification::where('user_id', $user->id)
                ->where('id', $id)
                ->first();

            if ($notif) {
                $notif->update(['is_read' => true]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error marking notification as read:', [
                'user_id' => $user->id ?? 'unknown',
                'notification_id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     */
    public function markAllRead()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read:', [
                'user_id' => $user->id ?? 'unknown',
                'message' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Admin: Ambil semua notifikasi (semua user).
     * Hanya bisa diakses oleh Manager & Partner.
     */
    public function getAllNotifications()
    {
        $user = Auth::user();

        if (!$user || !in_array($user->level_position, ['Manager', 'Partner'])) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        try {
            $notifications = Notification::with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            $formatted = $notifications->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'user_name' => $notif->user ? $notif->user->name : 'User Tidak Dikenal',
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'type' => $notif->type,
                    'is_read' => $notif->is_read,
                    'created_at' => $notif->created_at->format('d/m/Y H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formatted
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching all notifications:', [
                'admin_user_id' => $user?->id,
                'message' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}