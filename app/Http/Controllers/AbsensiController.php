<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Absensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AbsensiController extends Controller
{
    public function clockIn(Request $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $request->validate([
                'latitude'   => 'required|numeric',
                'longitude'  => 'required|numeric',
                'metode'     => 'required|in:qr,selfie',
                'qr_content' => 'required_if:metode,qr|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Data lokasi tidak lengkap.'], 422);
        }

        // === LOGIKA BARU: Validasi QR Code PRIBADI ===
        // Kita cek apakah QR yang discan adalah milik user yang sedang login?
        
        if ($request->metode === 'qr') {
            // 1. Ambil token yang discan
            $scannedToken = $request->qr_content;

            // 2. Ambil token asli milik user dari database
            $userToken = $user->attendance_token;

            // 3. Bandingkan
            if ($scannedToken !== $userToken) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR Code salah! Mohon scan QR Code (ID Card) Anda sendiri.'
                ], 401);
            }
        }
        // ==============================================

        // Cek Double Absen
        $todayAttendance = Absensi::where('user_id', $user->id)->whereDate('tanggal', Carbon::now()->toDateString())->first();
        if ($todayAttendance && $todayAttendance->jam_masuk) {
            return response()->json(['status' => 'warning', 'message' => 'Anda sudah absen masuk hari ini.'], 409); 
        }
        
        // Geofencing (Radius 200m)
        $kantorLat = env('OFFICE_LATITUDE', -7.71447); 
        $kantorLng = env('OFFICE_LONGITUDE', 110.31493);
        $jarak = $this->hitungJarak($kantorLat, $kantorLng, $request->latitude, $request->longitude); 
        if ($jarak > 1000000) return response()->json(['status' => 'error', 'message' => 'Kejauhan! Anda di luar jangkauan kantor.'], 400);
        
        // === LOGIKA STATUS (TERLAMBAT vs TEPAT WAKTU) ===
        // Aturan: Jam 09:30
        $jamBatasMasuk = Carbon::createFromTime(9, 30, 0);
        $sekarang = Carbon::now();
        $statusKehadiran = $sekarang->gt($jamBatasMasuk) ? 'Terlambat' : 'Tepat Waktu';

        // Simpan
        $newAttendance = Absensi::create([
            'user_id' => $user->id,
            'karyawan_id' => null,
            'tanggal' => $sekarang->toDateString(),
            'jam_masuk' => $sekarang->toTimeString(),
            'metode' => $request->metode,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $statusKehadiran, // <--- STATUS DISIMPAN DISINI
        ]);

        // Tambahkan DI DALAM block try{} di method clockIn(), setelah $newAttendance dibuat

        if ($statusKehadiran === 'Terlambat') {
            $admins = \App\Models\User::where('posisi_id', 1)->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Karyawan Terlambat',
                    'message' => $user->name . ' terlambat masuk pada ' . $sekarang->toDateString() . '. Jam masuk: ' . $sekarang->format('H:i'),
                    'type' => 'late_attendance',
                    'is_read' => false,
                ]);
            }
        }

        // Pesan dinamis
        $pesan = $statusKehadiran === 'Terlambat' 
            ? 'Absen berhasil, tapi Anda terlambat (Lewat 09:30).' 
            : 'Absen berhasil! Anda tepat waktu.';

        return response()->json([
            'status' => 'success',
            'message' => $pesan,
            'attendance' => $newAttendance
        ]);
    }
    
    public function clockOut(Request $request): JsonResponse
    {
        $user = Auth::user();
        $now = Carbon::now();
        
        // === LOGIKA JAM PULANG (MINIMAL JAM 15:00) ===
        $jamBatasPulang = Carbon::createFromTime(15, 0, 0); // Jam 3 Sore

        // Jika pulang sebelum jam 3 sore
        if ($now->lt($jamBatasPulang)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum jam pulang! Jam pulang adalah pukul 15:00.'
            ], 400);
        }

        // Cek data absen hari ini
        $absensi = Absensi::where('user_id', $user->id)
                           ->whereDate('tanggal', $now->toDateString())
                           ->whereNotNull('jam_masuk') 
                           ->whereNull('jam_pulang')  
                           ->first();

        if (!$absensi) {
            return response()->json(['status' => 'warning', 'message' => 'Belum absen masuk atau sudah pulang.'], 409); 
        }

        // Geofencing Pulang (Opsional, bisa dihapus kalau boleh pulang dari mana saja)
        $kantorLat = env('OFFICE_LATITUDE', -7.71447);
        $kantorLng = env('OFFICE_LONGITUDE', 110.31493);
        $jarak = $this->hitungJarak($kantorLat, $kantorLng, $request->latitude, $request->longitude);
        if ($jarak > 1000000) return response()->json(['status' => 'error', 'message' => 'Anda harus berada di area kantor untuk Clock Out.'], 400);

        $absensi->update(['jam_pulang' => $now->toTimeString()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Hati-hati di jalan! Absen pulang berhasil.',
            'attendance' => $absensi
        ]);
    }


    public function riwayat(Request $request): JsonResponse
    {
        $user = Auth::user();
        $riwayat = Absensi::where('user_id', $user->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Riwayat Berhasil',
            'data' => $riwayat
        ]);
    }

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