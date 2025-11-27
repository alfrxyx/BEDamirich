<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // <--- Tambahkan ini

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Cek Login
        if (!Auth::check()) {
            Log::info('IsAdmin Middleware: User belum login / Token tidak terbaca.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // 2. CATAT DATA USER KE LOG (Ini kuncinya!)
        Log::info('IsAdmin Middleware Cek User:', [
            'id' => $user->id,
            'email' => $user->email,
            'posisi_id' => $user->posisi_id,
            'tipe_data_posisi' => gettype($user->posisi_id)
        ]);

        // 3. Cek Jabatan (Pakai validasi longgar dulu '==' bukan '===')
        if ($user->posisi_id != 1) {
            return response()->json(['message' => 'Forbidden: Anda bukan Admin (Posisi ID: ' . $user->posisi_id . ')'], 403);
        }

        return $next($request);
    }
}