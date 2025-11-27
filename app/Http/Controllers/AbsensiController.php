<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceToken; 
use App\Models\Absensi;          
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AbsensiController extends Controller
{
    /**
     * Memproses Absensi Clock-In menggunakan Token QR.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // --- PERBAIKAN PENTING: CEK KARYAWAN ---
        // Pastikan user yang login sudah terhubung dengan data karyawan
        if (!$user->karyawan) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Data karyawan tidak ditemukan. Hubungi Admin.'
            ], 404);
        }

        try {
            $request->validate([
                'latitude'   => 'required|numeric',
                'longitude'  => 'required|numeric',
                'metode'     => 'required|in:qr,selfie',
                'qr_content' => 'required_if:metode,qr|string|size:32',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal: Token atau data lokasi tidak lengkap.'], 422);
        }

        $token = $request->qr_content;
        $now = Carbon::now();

        if ($request->metode === 'qr') {
            $attendanceToken = AttendanceToken::where('token', $token)->where('is_active', true)->first();
            if (!$attendanceToken) {
                return response()->json(['status' => 'error', 'message' => 'Token QR tidak valid atau sudah tidak aktif.'], 401);
            }
            if ($now->greaterThan($attendanceToken->expires_at)) {
                $attendanceToken->update(['is_active' => false]); 
                return response()->json(['status' => 'error', 'message' => 'Token QR telah kedaluwarsa.'], 401);
            }
        }

        // Gunakan ID KARYAWAN, bukan ID USER
        $karyawanId = $user->karyawan->id;

        $todayAttendance = Absensi::where('karyawan_id', $karyawanId)
                                    ->whereDate('tanggal', $now->toDateString())
                                    ->first();

        if ($todayAttendance && $todayAttendance->jam_masuk) {
            return response()->json(['status' => 'warning', 'message' => 'Anda sudah melakukan Clock-In hari ini.'], 409); 
        }
        
        // --- Geofencing ---
        $kantorLat = env('OFFICE_LATITUDE', -6.175392); 
        $kantorLng = env('OFFICE_LONGITUDE', 106.827153);
        $radiusMax = env('OFFICE_RADIUS_LIMIT', 200);

        $jarak = $this->hitungJarak($kantorLat, $kantorLng, $request->latitude, $request->longitude); 
        
        if ($jarak > $radiusMax) {
            return response()->json(['status' => 'error', 'message' => 'Gagal! Anda berada di luar jangkauan kantor.'], 400);
        }
        
        // Proses Clock-In
        $newAttendance = Absensi::create([
            'karyawan_id' => $karyawanId, // <--- INI YANG TADI SALAH ($user->id)
            'tanggal' => $now->toDateString(),
            'jam_masuk' => $now->toTimeString(),
            'metode' => $request->metode,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'tepat waktu',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Clock-In berhasil! Selamat bekerja.',
            'attendance' => $newAttendance
        ]);
    }
    
    /**
     * Memproses Absensi Clock-Out (Absen Pulang).
     */
    public function clockOut(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Cek karyawan juga di sini
        if (!$user->karyawan) {
            return response()->json(['status' => 'error', 'message' => 'Data karyawan invalid.'], 404);
        }

        $now = Carbon::now();
        $today = $now->toDateString();
        
        try {
            $request->validate([
                'latitude'   => 'required|numeric',
                'longitude'  => 'required|numeric',
                'metode'     => 'required|in:qr,selfie',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Data lokasi tidak valid.'], 422);
        }

        // Gunakan ID KARYAWAN
        $karyawanId = $user->karyawan->id;

        $absensi = Absensi::where('karyawan_id', $karyawanId)
                           ->whereDate('tanggal', $today)
                           ->whereNotNull('jam_masuk') 
                           ->whereNull('jam_pulang')  
                           ->first();

        if (!$absensi) {
            return response()->json(['status' => 'warning', 'message' => 'Anda belum absen masuk hari ini atau sudah pulang.'], 409); 
        }

        // --- Geofencing ---
        $kantorLat = env('OFFICE_LATITUDE', -6.175392); 
        $kantorLng = env('OFFICE_LONGITUDE', 106.827153);
        $radiusMax = env('OFFICE_RADIUS_LIMIT', 200);

        $jarak = $this->hitungJarak($kantorLat, $kantorLng, $request->latitude, $request->longitude);
        
        if ($jarak > $radiusMax) {
            return response()->json(['status' => 'error', 'message' => 'Gagal! Anda berada di luar jangkauan kantor saat pulang.'], 400);
        }
        
        // Proses Clock-Out
        $absensi->update([
            'jam_pulang' => $now->toTimeString(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Clock-Out berhasil! Sampai jumpa besok.',
            'attendance' => $absensi
        ]);
    }

    /**
     * Mengambil riwayat absensi.
     */
    public function riwayat(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->karyawan) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        // Gunakan ID KARYAWAN
        $riwayat = Absensi::where('karyawan_id', $user->karyawan->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Riwayat Absensi Berhasil Diambil',
            'data' => $riwayat 
        ]);
    }

    /**
     * Fungsi Haversine untuk menghitung jarak.
     */
    private function hitungJarak($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}