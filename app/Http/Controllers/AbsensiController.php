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
                'qr_content' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Data lokasi tidak lengkap.'], 422);
        }

        // ✅ Validasi QR hanya jika metode = 'qr'
        if ($request->metode === 'qr') {
            if (empty($request->qr_content)) {
                return response()->json(['status' => 'error', 'message' => 'QR Code tidak boleh kosong.'], 422);
            }

            // ✅ Bandingkan langsung dengan attendance_token
            if (trim($request->qr_content) !== $user->attendance_token) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'QR Code salah! Mohon scan QR Code Anda sendiri.'
                ], 401);
            }
        }

        // Cek absen ganda
        $todayAttendance = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', Carbon::now()->toDateString())
            ->first();

        if ($todayAttendance && $todayAttendance->jam_masuk) {
            return response()->json(['status' => 'warning', 'message' => 'Anda sudah absen masuk hari ini.'], 409);
        }

        // Geofencing (radius 1000 km — non-restrictive)
        $kantorLat = env('OFFICE_LATITUDE', -7.71447);
        $kantorLng = env('OFFICE_LONGITUDE', 110.31493);
        $jarak = $this->hitungJarak($kantorLat, $kantorLng, $request->latitude, $request->longitude);
        if ($jarak > 1000000) {
            return response()->json(['status' => 'error', 'message' => 'Lokasi tidak valid.'], 400);
        }

        // Tentukan status kehadiran
        $jamBatasMasuk = Carbon::createFromTime(9, 30, 0);
        $sekarang = Carbon::now();
        $statusKehadiran = $sekarang->gt($jamBatasMasuk) ? 'Terlambat' : 'Tepat Waktu';

        // Simpan absensi
        $newAttendance = Absensi::create([
            'user_id' => $user->id,
            'tanggal' => $sekarang->toDateString(),
            'jam_masuk' => $sekarang->toTimeString(),
            'metode' => $request->metode,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $statusKehadiran,
            'is_manual' => $request->metode === 'selfie',
        ]);

        // Kirim notifikasi ke admin jika terlambat
        if ($statusKehadiran === 'Terlambat') {
            $admins = \App\Models\User::whereHas('posisi', function ($q) {
                $q->whereIn('nama', ['COO', 'CEO', 'Manager', 'HRD']);
            })->get();

            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Karyawan Terlambat',
                    'message' => "{$user->name} terlambat masuk pada {$sekarang->format('Y-m-d H:i')}.",
                    'type' => 'late_attendance',
                    'is_read' => false,
                ]);
            }
        }

        $pesan = $statusKehadiran === 'Terlambat'
            ? 'Absen berhasil, tapi Anda terlambat (lewat pukul 09:30).'
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

        if ($now->lt(Carbon::createFromTime(15, 0, 0))) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'Belum jam pulang! Jam pulang pukul 15:00.'
            ], 403);
        }

        $absensi = Absensi::where('user_id', $user->id)
            ->whereDate('tanggal', $now->toDateString())
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_pulang')
            ->first();

        if (!$absensi) {
            return response()->json(['status' => 'warning', 'message' => 'Belum absen masuk atau sudah pulang.'], 409);
        }

        // Opsional: validasi lokasi pulang
        $kantorLat = env('OFFICE_LATITUDE', -7.71447);
        $kantorLng = env('OFFICE_LONGITUDE', 110.31493);
        $jarak = $this->hitungJarak($kantorLat, $kantorLng, $request->latitude, $request->longitude);
        if ($jarak > 1000000) {
            return response()->json(['status' => 'error', 'message' => 'Lokasi tidak valid.'], 400);
        }

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
            'message' => 'Riwayat absensi berhasil dimuat.',
            'data' => $riwayat
        ]);
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}