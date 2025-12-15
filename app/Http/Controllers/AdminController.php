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
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Notification;

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

        /**
 * GET /api/admin/attendances/today
 * Mendapatkan daftar absensi karyawan hari ini
 */
public function getTodayAttendances(): JsonResponse
{
    $today = Carbon::today()->toDateString();
    
    $attendances = Absensi::with(['user.divisi'])
        ->whereDate('tanggal', $today)
        ->orderBy('jam_masuk', 'asc')
        ->get()
        ->map(function($absensi) {
            $jamMasuk = Carbon::parse($absensi->jam_masuk);
            $batasWaktu = Carbon::parse('09:00:00');
            
            // DEBUG - Log untuk lihat nilai
            \Log::info('DEBUG Absensi', [
                'nama' => $absensi->user->name ?? 'Unknown',
                'jam_masuk' => $absensi->jam_masuk,
                'jam_masuk_carbon' => $jamMasuk->toTimeString(),
                'batas_waktu' => $batasWaktu->toTimeString(),
                'diff_minutes' => $jamMasuk->diffInMinutes($batasWaktu),
                'is_late' => $jamMasuk->greaterThan($batasWaktu)
            ]);
            
            $isLate = $absensi->status === 'Terlambat' || $jamMasuk->greaterThan($batasWaktu);
            $statusLabel = $isLate ? 'late' : 'on_time';
            
            $keterangan = '';
            if ($isLate) {
                // PERBAIKAN: Gunakan absolute value
                $totalMenit = abs(round($jamMasuk->diffInMinutes($batasWaktu)));
                
                if ($totalMenit >= 60) {
                    $jam = floor($totalMenit / 60);
                    $menit = $totalMenit % 60;
                    $keterangan = "Terlambat {$jam} jam {$menit} menit";
                } else {
                    $keterangan = "Terlambat {$totalMenit} menit";
                }
            }
            
            return [
                'id' => $absensi->id,
                'karyawan' => [
                    'nama' => $absensi->user ? $absensi->user->name : 'Unknown User',
                    'divisi' => [
                        'nama' => $absensi->user && $absensi->user->divisi 
                            ? $absensi->user->divisi->nama 
                            : '-'
                    ]
                ],
                'tanggal' => $absensi->tanggal,
                'jam_masuk' => $absensi->jam_masuk,
                'status' => $statusLabel,
                'keterangan' => $keterangan
            ];
        });

    return response()->json([
        'message' => 'Data absensi hari ini berhasil dimuat',
        'data' => $attendances
    ]);
}
    public function exportLaporanPDF(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:absensi,cuti'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $type = $request->type;

        // Ambil Data
        if ($type === 'absensi') {
            $data = Absensi::with('user.divisi')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal', 'desc')
                ->orderBy('jam_masuk', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'tanggal' => Carbon::parse($item->tanggal)->format('d/m/Y'),
                        'nama' => $item->user ? $item->user->name : 'Unknown',
                        'divisi' => $item->user && $item->user->divisi ? $item->user->divisi->nama : '-',
                        'jam_masuk' => $item->jam_masuk ? Carbon::parse($item->jam_masuk)->format('H:i') : '-',
                        'jam_pulang' => $item->jam_pulang ? Carbon::parse($item->jam_pulang)->format('H:i') : 'Belum Pulang',
                        'status' => $item->status ?? '-',
                        'durasi' => $this->hitungDurasi($item->jam_masuk, $item->jam_pulang)
                    ];
                });

            $title = 'Laporan Absensi Karyawan';
        } else {
            $data = LeaveRequest::with('user')
                ->whereBetween('start_date', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'tanggal' => Carbon::parse($item->start_date)->format('d/m/Y') . ' - ' . Carbon::parse($item->end_date)->format('d/m/Y'),
                        'nama' => $item->user ? $item->user->name : 'Unknown',
                        'divisi' => $item->user && $item->user->divisi ? $item->user->divisi->nama : '-',
                        'jenis' => ucfirst($item->type),
                        'alasan' => $item->reason,
                        'status' => $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Menunggu')
                    ];
                });

            $title = 'Laporan Cuti Karyawan';
        }

        // Data untuk PDF
        $pdfData = [
            'title' => $title,
            'type' => $type,
            'data' => $data,
            'periode' => $startDate->format('d/m/Y') . ' s/d ' . $endDate->format('d/m/Y'),
            'tanggal_cetak' => Carbon::now()->format('d/m/Y H:i'),
            'dicetak_oleh' => Auth::user()->name ?? 'Admin'
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.laporan-absensi', $pdfData)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => true
                ]);

        // Download PDF
        $filename = strtolower(str_replace(' ', '-', $title)) . '-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.pdf';
        
        return $pdf->download($filename);
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
    
    if ((int)$user->posisi_id !== 1) {
        return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $request->validate([
        'status' => 'required|in:approved,rejected'
    ]);

    $leaveRequest = LeaveRequest::with('user')->find($id);

    if (!$leaveRequest) {
        return response()->json(['message' => 'Data cuti tidak ditemukan'], 404);
    }

    $leaveRequest->status = $request->status;
    $leaveRequest->save();

    // 🔔 INI YANG WAJIB ADA — KIRIM NOTIFIKASI KE USER
    $statusLabel = $request->status === 'approved' ? 'Disetujui' : 'Ditolak';
    \App\Models\Notification::create([
        'user_id' => $leaveRequest->user_id,
        'title' => 'Permohonan Cuti ' . $statusLabel,
        'message' => 'Permohonan cuti Anda dari ' . $leaveRequest->start_date . ' hingga ' . $leaveRequest->end_date . ' telah ' . strtolower($statusLabel) . '.',
        'type' => 'leave_status_update',
        'is_read' => false,
    ]);

    return response()->json([
        'message' => 'Status permohonan berhasil diperbarui menjadi ' . $statusLabel,
        'data' => $leaveRequest
    ]);
}

    // ... kode-kode sebelumnya ...

    // ==========================================
    // FITUR 5: LAPORAN / REKAP DATA (SESUAI DIAGRAM)
    // ==========================================

    public function getRekapLaporan(Request $request)
{
    \Log::info('=== START getRekapLaporan ===', [
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'type' => $request->type,
        'user_id' => Auth::id()
    ]);

    try {
        // Validasi Input Filter
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:absensi,cuti'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Ambil Data Absensi
        if ($request->type === 'absensi') {
            $data = Absensi::with('user')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal', 'desc')
                ->orderBy('jam_masuk', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'tanggal' => $item->tanggal,
                        'nama_karyawan' => $item->user ? $item->user->name : 'Unknown User',
                        'jam_masuk' => $item->jam_masuk,
                        'jam_pulang' => $item->jam_pulang,
                        'status' => $item->status,
                        'keterangan' => $this->hitungDurasi($item->jam_masuk, $item->jam_pulang)
                    ];
                });

            // Jika tidak ada data
            if ($data->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada data absensi pada periode ini.',
                    'data' => []
                ]);
            }

            return response()->json([
                'message' => 'Data laporan absensi berhasil diambil',
                'data' => $data
            ]);

        } else {
            // Laporan Cuti: Ambil data cuti dalam periode
            $data = LeaveRequest::with('user')
                ->whereBetween('start_date', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'tanggal' => $item->start_date . ' s/d ' . $item->end_date, // Rentang tanggal cuti
                        'nama_karyawan' => $item->user ? $item->user->name : 'Unknown',
                        'jam_masuk' => $item->type, // Gunakan kolom ini untuk "Jenis Cuti"
                        'jam_pulang' => $item->reason, // Gunakan untuk "Alasan"
                        'status' => $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Menunggu'),
                        'keterangan' => '' // Kosongkan, karena info sudah di kolom lain
                    ];
                });

            return response()->json([
                'message' => 'Data laporan cuti berhasil diambil',
                'data' => $data
            ]);
        }

    } catch (\Exception $e) {
        \Log::error('ERROR in getRekapLaporan:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'message' => 'Terjadi kesalahan internal server.',
            'error' => $e->getMessage()
        ], 500);
    }
}
// Helper hitung durasi kerja (optional, untuk mempercantik laporan)
private function hitungDurasi($masuk, $pulang) {
    if (!$masuk) return '-';
    if (!$pulang) return 'Belum Pulang';

    try {
        $start = Carbon::parse($masuk);
        $end = Carbon::parse($pulang);
        $diff = $start->diff($end);
        return $diff->format('%H Jam %I Menit');
    } catch (\Exception $e) {
        return '-';
    }
}
}