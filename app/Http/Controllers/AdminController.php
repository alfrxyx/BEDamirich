<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\AttendanceToken; 
use App\Models\LeaveRequest; 
use App\Models\User;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ==========================================
    // FITUR 1: DASHBOARD & STATISTIK
    // ==========================================

        public function dashboardHarian(): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        // 1. Hitung Total Karyawan (Kecuali Admin ID 1)
        $totalEmployees = User::where('posisi_id', '!=', 1)->count();

        // 2. Hitung Yang Sudah Absen Hari Ini
        $presentToday = Absensi::whereDate('tanggal', $today)->count();

        // 3. Hitung Yang Terlambat Hari Ini
        // Kita hitung berdasarkan status 'Terlambat' yang disimpan saat absen
        $lateEntries = Absensi::whereDate('tanggal', $today)
                              ->where('status', 'Terlambat')
                              ->count();

        return response()->json([
            'message' => 'Data dashboard admin berhasil dimuat',
            'stats' => [
                'total_employees' => $totalEmployees, 
                'present_today' => $presentToday,   
                'late_entries' => $lateEntries,
            ]
        ]);
    }

    public function rekapSemua(): JsonResponse
    {
        return response()->json([
            'message' => 'Laporan rekap semua data berhasil dimuat',
            'data' => []
        ]);
    }

    // ==========================================
    // FITUR 2: GENERATE QR TOKEN
    // ==========================================

    public function generateAttendanceToken(): JsonResponse
    {
        $token = Str::random(32);
        $expiresAt = Carbon::now()->addMinutes(10);
        
        // Nonaktifkan token lama agar tidak double
        AttendanceToken::where('is_active', true)->update(['is_active' => false]);

        AttendanceToken::create([
            'token' => $token,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'token_saat_ini' => $token,
            'qr_image_content' => $token, 
            'expires_at' => $expiresAt->toDateTimeString(),
            'message' => 'Token absensi berhasil dibuat.'
        ]);
    }

    // ==========================================
    // FITUR 3: PERSETUJUAN CUTI (FIXED)
    // ==========================================

    public function getLeaveRequests()
    {
        $user = Auth::user();
        
        // 1. CEK ADMIN (Gunakan data dari tabel Users, bukan Karyawan)
        if ((int)$user->posisi_id !== 1) {
            return response()->json(['message' => 'Akses ditolak. Area khusus Admin.'], 403);
        }

        // 2. Ambil data cuti dengan relasi User
        $requests = LeaveRequest::with(['user']) // Kita ambil relasi ke user saja
                                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                                ->orderBy('created_at', 'desc')
                                ->get();

        // 3. Format Data
        $formattedRequests = $requests->map(function ($leave) {
            
            // Ambil nama user (karena tabel users yang punya nama & divisi_id)
            $namaKaryawan = $leave->user ? $leave->user->name : 'User Tidak Dikenal';
            
            // Kalau mau ambil nama divisi, kita perlu relasi di User model ke Divisi
            // Untuk sementara kita hardcode atau ambil dari data user jika ada
            $namaDivisi = '-'; 
            
            return [
                'id' => $leave->id,
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'type' => $leave->type,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'created_at' => $leave->created_at->format('Y-m-d H:i'), // Format tanggal biar rapi
                'karyawan' => [
                    'nama' => $namaKaryawan,
                    'divisi' => ['nama' => $namaDivisi]
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedRequests
        ]);
    }

    public function getAllEmployees()
    {
        // Ambil user yang posisinya BUKAN Admin (ID 1)
        // Kita load juga relasi divisinya biar nama divisinya muncul
        $employees = User::where('posisi_id', '!=', 1)
                         ->with('divisi') 
                         ->orderBy('created_at', 'desc')
                         ->get();

        return response()->json([
            'message' => 'Data karyawan berhasil diambil',
            'data' => $employees
        ]);
    }

    // 2. Tambah Karyawan Baru
    public function addEmployee(Request $request)
    {
        // Validasi Input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'divisi_id' => 'required|exists:divisis,id',
            'tanggal_masuk' => 'required|date',
        ]);

        try {
            // GENERATE TOKEN OTOMATIS DI SINI
            // Token ini permanen untuk user tersebut
            $permanentToken = (string) Str::uuid();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'divisi_id' => $request->divisi_id,
                'posisi_id' => 2, // Default: Staff (Karyawan Biasa)
                'tanggal_masuk' => $request->tanggal_masuk,
                'attendance_token' => $permanentToken, // <--- INI KUNCINYA
            ]);

            return response()->json([
                'message' => 'Karyawan berhasil ditambahkan!',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah karyawan', 'error' => $e->getMessage()], 500);
        }
    }

    // ... kode sebelumnya ...

    // 3. Hapus Karyawan
    public function deleteEmployee($id)
    {
        // Cari user
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan'], 404);
        }

        // PENTING: Cegah menghapus akun Admin Utama (ID 1) atau sesama Admin
        if ($user->posisi_id === 1) {
            return response()->json(['message' => 'DILARANG: Anda tidak bisa menghapus akun Admin!'], 403);
        }

        // Hapus User
        $user->delete();

        return response()->json([
            'message' => 'Karyawan berhasil dihapus secara permanen.'
        ]);
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        // Cek Admin (Konsisten pakai posisi_id di tabel Users)
        if ((int)$user->posisi_id !== 1) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $leaveRequest = LeaveRequest::find($id);

        if (!$leaveRequest) {
            return response()->json(['message' => 'Data cuti tidak ditemukan'], 404);
        }

        $leaveRequest->status = $request->status;
        $leaveRequest->save();

        return response()->json([
            'message' => 'Status permohonan berhasil diperbarui menjadi ' . $request->status,
            'data' => $leaveRequest
        ]);
    }
}