<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function index()
    {
        // Log 1: Cek siapa user yang login
        $user = auth('api')->user();
        Log::info('NotificationController@index - User Info', [
            'user' => $user,
            'user_type' => gettype($user),
            'user_class' => $user ? get_class($user) : 'null',
            'user_id' => $user ? $user->id ?? 'no id' : 'null'
        ]);

        if (!$user || !($user instanceof \App\Models\User)) {
            Log::warning('NotificationController@index: User not authenticated or invalid model.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Log 2: Cek apakah model Notification bisa diakses
            Log::info('NotificationController@index - Trying to fetch notifications for user ID: ' . $user->id);

            $notifications = Notification::where('user_id', $user->id)
                                         ->orderBy('created_at', 'desc')
                                         ->take(10)
                                         ->get();

            // Log 3: Data notifikasi
            Log::info('NotificationController@index - Fetched notifications:', [
                'count' => $notifications->count(),
                'data' => $notifications->toArray()
            ]);

            $unreadCount = Notification::where('user_id', $user->id)
                                       ->where('is_read', false)
                                       ->count();

            Log::info('NotificationController@index - Unread count: ' . $unreadCount);

            return response()->json([
                'data' => $notifications,
                'unread_count' => $unreadCount
            ]);

        } catch (\Exception $e) {
            // Log 4: Error detail
            Log::error('NotificationController@index ERROR:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? 'unknown'
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function markAsRead($id)
    {
        $user = auth('api')->user();
        if (!$user) {
            Log::warning('NotificationController@markAsRead: User not authenticated.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $notif = Notification::where('user_id', $user->id)->where('id', $id)->first();
            if ($notif) {
                $notif->update(['is_read' => true]);
            }
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('NotificationController@markAsRead ERROR:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? 'unknown',
                'notification_id' => $id
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function markAllRead()
    {
        $user = auth('api')->user();
        if (!$user) {
            Log::warning('NotificationController@markAllRead: User not authenticated.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            Notification::where('user_id', $user->id)
                        ->where('is_read', false)
                        ->update(['is_read' => true]);
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('NotificationController@markAllRead ERROR:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? 'unknown'
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}