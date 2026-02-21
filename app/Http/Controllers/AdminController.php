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
    // FITUR 1: DASHBOARD & STATISTIK (Untuk Semua Admin)
    // ==========================================

    public function dashboardHarian(): JsonResponse
    {
        $user = Auth::user();
        
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $today = Carbon::today()->toDateString();

        $totalEmployees = User::where('level_position', '!=', 'Partner')->count();
        $presentToday = Absensi::whereDate('tanggal', $today)->count();
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
        $user = Auth::user();
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json([
            'message' => 'Laporan rekap semua data berhasil dimuat',
            'data' => []
        ]);
    }

    // ==========================================
    // FITUR 2: GENERATE QR TOKEN (Untuk Semua Admin)
    // ==========================================

    public function generateAttendanceToken(): JsonResponse
    {
        $user = Auth::user();
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $token = Str::random(32);
        $expiresAt = Carbon::now()->addMinutes(10);
        
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
    // FITUR 3: PERSETUJUAN CUTI (Untuk Semua Admin)
    // ==========================================

    public function getLeaveRequests()
    {
        $user = Auth::user();
        
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $requests = LeaveRequest::with(['user'])
                                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                                ->orderBy('created_at', 'desc')
                                ->get();

        $formattedRequests = $requests->map(function ($leave) {
            $namaKaryawan = $leave->user ? $leave->user->name : 'User Tidak Dikenal';
            
            return [
                'id' => $leave->id,
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'type' => $leave->type,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'created_at' => $leave->created_at->format('Y-m-d H:i'),
                'karyawan' => [
                    'nama' => $namaKaryawan,
                    'divisi' => ['nama' => '-']
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedRequests
        ]);
    }

    public function updateLeaveStatus(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
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

        $statusLabel = $request->status === 'approved' ? 'Disetujui' : 'Ditolak';
        Notification::create([
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

    // ==========================================
    // FITUR 4: ABSENSI HARI INI (Untuk Semua Admin)
    // ==========================================

    public function getTodayAttendances(): JsonResponse
    {
        $user = Auth::user();
        
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $today = Carbon::today()->toDateString();
        
        $attendances = Absensi::with(['user.divisi'])
            ->whereDate('tanggal', $today)
            ->orderBy('jam_masuk', 'asc')
            ->get()
            ->map(function($absensi) {
                $jamMasuk = Carbon::parse($absensi->jam_masuk);
                $batasWaktu = Carbon::parse('09:00:00');
                $isLate = $absensi->status === 'Terlambat' || $jamMasuk->greaterThan($batasWaktu);
                $statusLabel = $isLate ? 'late' : 'on_time';
                
                $keterangan = '';
                if ($isLate) {
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
                                ? $absensi->user->divisi->name
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

    // ==========================================
    // FITUR 5: EXPORT PDF (Untuk Semua Admin)
    // ==========================================

    public function exportLaporanPDF(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:absensi,cuti'
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $type = $request->type;

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
                        'divisi' => $item->user && $item->user->divisi ? $item->user->divisi->name : '-',
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
                        'divisi' => $item->user && $item->user->divisi ? $item->user->divisi->name : '-',
                        'jenis' => ucfirst($item->type),
                        'alasan' => $item->reason,
                        'status' => $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Menunggu')
                    ];
                });
            $title = 'Laporan Cuti Karyawan';
        }

        $pdfData = [
            'title' => $title,
            'type' => $type,
            'data' => $data,
            'periode' => $startDate->format('d/m/Y') . ' s/d ' . $endDate->format('d/m/Y'),
            'tanggal_cetak' => Carbon::now()->format('d/m/Y H:i'),
            'dicetak_oleh' => Auth::user()->name ?? 'Admin'
        ];

        $pdf = Pdf::loadView('pdf.laporan-absensi', $pdfData)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => true
                ]);

        $filename = strtolower(str_replace(' ', '-', $title)) . '-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.pdf';
        
        return $pdf->download($filename);
    }

    // ==========================================
    // ✅ FITUR BARU: EXPORT LAPORAN BULANAN PDF (MENGGUNAKAN TEMPLATE YANG SAMA)
    // ==========================================
    public function exportLaporanBulananPDF(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $bulan = $request->input('bulan', date('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $bulan)->endOfMonth();

        // Ambil data seperti di getMonthlyDetail()
        $users = User::where('status_aktif', 'aktif')
            ->where('level_position', '!=', 'Partner')
            ->with('divisi')
            ->get();

        $data = [];

        foreach ($users as $user) {
            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal', 'asc')
                ->get()
                ->map(function ($a) {
                    return [
                        'tanggal' => Carbon::parse($a->tanggal)->format('d/m/Y'),
                        'nama' => $a->user->name,
                        'divisi' => $a->user->divisi ? $a->user->divisi->name : '-',
                        'jam_masuk' => $a->jam_masuk ? Carbon::parse($a->jam_masuk)->format('H:i') : '-',
                        'jam_pulang' => $a->jam_pulang ? Carbon::parse($a->jam_pulang)->format('H:i') : '-',
                        'status' => $a->status ?? '-',
                        'durasi' => $this->hitungDurasi($a->jam_masuk, $a->jam_pulang),
                    ];
                });

            $cuti = LeaveRequest::where('user_id', $user->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end]);
                })
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($c) {
                    return [
                        'tanggal' => Carbon::parse($c->start_date)->format('d/m/Y') . ' - ' . Carbon::parse($c->end_date)->format('d/m/Y'),
                        'nama' => $c->user->name,
                        'divisi' => $c->user->divisi ? $c->user->divisi->name : '-',
                        'jenis' => ucfirst($c->type),
                        'alasan' => $c->reason,
                        'status' => $c->status === 'approved' ? 'Disetujui' : 
                                   ($c->status === 'rejected' ? 'Ditolak' : 'Menunggu'),
                    ];
                });

            // Gabungkan absensi dan cuti ke dalam satu array datanya
            $data = array_merge($data, $absensi->toArray(), $cuti->toArray());
        }

        // Tentukan tipe berdasarkan isi data
        $hasAbsensi = collect($data)->contains('jam_masuk');
        $hasCuti = collect($data)->contains('jenis');

        if ($hasAbsensi && $hasCuti) {
            // Jika ada keduanya, buat dua bagian terpisah
            $absensiData = array_filter($data, fn($item) => isset($item['jam_masuk']));
            $cutiData = array_filter($data, fn($item) => isset($item['jenis']));

            // Export absensi dulu
            $pdf1 = Pdf::loadView('pdf.laporan-absensi', [
                'title' => 'Laporan Bulanan Absensi',
                'type' => 'absensi',
                'data' => $absensiData,
                'periode' => $start->format('d M Y') . ' - ' . $end->format('d M Y'),
                'tanggal_cetak' => Carbon::now()->format('d/m/Y H:i'),
                'dicetak_oleh' => Auth::user()->name ?? 'Admin'
            ])->setPaper('a4', 'landscape');

            // Export cuti
            $pdf2 = Pdf::loadView('pdf.laporan-absensi', [
                'title' => 'Laporan Bulanan Cuti',
                'type' => 'cuti',
                'data' => $cutiData,
                'periode' => $start->format('d M Y') . ' - ' . $end->format('d M Y'),
                'tanggal_cetak' => Carbon::now()->format('d/m/Y H:i'),
                'dicetak_oleh' => Auth::user()->name ?? 'Admin'
            ])->setPaper('a4', 'landscape');

            // Gabungkan PDF (opsional, atau kirim terpisah)
            // Untuk kesederhanaan, kita kirim hanya absensi jika ada, atau cuti
            $pdf = $hasAbsensi ? $pdf1 : $pdf2;
            $title = $hasAbsensi ? 'Laporan Bulanan Absensi' : 'Laporan Bulanan Cuti';
        } else {
            $type = $hasAbsensi ? 'absensi' : 'cuti';
            $title = $hasAbsensi ? 'Laporan Bulanan Absensi' : 'Laporan Bulanan Cuti';

            $pdf = Pdf::loadView('pdf.laporan-absensi', [
                'title' => $title,
                'type' => $type,
                'data' => $data,
                'periode' => $start->format('d M Y') . ' - ' . $end->format('d M Y'),
                'tanggal_cetak' => Carbon::now()->format('d/m/Y H:i'),
                'dicetak_oleh' => Auth::user()->name ?? 'Admin'
            ])->setPaper('a4', 'landscape');
        }

        $filename = 'laporan-bulanan-' . $start->format('Ym') . '.pdf';
        return $pdf->download($filename);
    }

    // ==========================================
    // FITUR 6: MANAJEMEN KARYAWAN — SUPER ADMIN & ADMIN BIASA
    // ==========================================

    public function getAllemployees()
    {
        $user = Auth::user();
        
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($user->level_position === 'Partner') {
            $employees = User::where('level_position', '!=', 'Partner')
                             ->orWhere('id', $user->id)
                             ->with('divisi')
                             ->orderBy('created_at', 'desc')
                             ->get();
        } else {
            $employees = User::where('level_position', 'Employee')
                             ->with('divisi')
                             ->orderBy('created_at', 'desc')
                             ->get();
        }

        return response()->json([
            'message' => 'Data karyawan berhasil diambil',
            'data' => $employees
        ]);
    }

    public function addEmployee(Request $request)
    {
        $user = Auth::user();
        if ($user->level_position !== 'Partner') {
            return response()->json(['message' => 'Akses ditolak. Hanya Super Admin yang bisa menambah karyawan.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'divisi_id' => 'required|exists:divisi,id',
            'tanggal_masuk' => 'required|date',
        ]);

        try {
            do {
                $userIdCode = 'DMR' . strtoupper(Str::random(5));
            } while (User::where('user_id_code', $userIdCode)->exists());

            $permanentToken = (string) Str::uuid();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'divisi_id' => $request->divisi_id,
                'posisi_id' => 2,
                'tanggal_masuk' => $request->tanggal_masuk,
                'user_id_code' => $userIdCode,
                'attendance_token' => $permanentToken,
                'level_position' => 'Employee',
            ]);

            return response()->json([
                'message' => 'Karyawan berhasil ditambahkan!',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambah karyawan', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteEmployee($id)
    {
        $user = Auth::user();
        if ($user->level_position !== 'Partner') {
            return response()->json(['message' => 'Akses ditolak. Hanya Super Admin yang bisa menghapus karyawan.'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan'], 404);
        }

        if ($targetUser->level_position === 'Partner') {
            return response()->json(['message' => 'DILARANG: Anda tidak bisa menghapus akun Super Admin lain!'], 403);
        }

        $targetUser->delete();

        return response()->json([
            'message' => 'Karyawan berhasil dihapus secara permanen.'
        ]);
    }

    // ==========================================
    // FITUR 7: REKAP LAPORAN (Untuk Semua Admin)
    // ==========================================

    public function getRekapLaporan(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'type' => 'required|in:absensi,cuti'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

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
                $data = LeaveRequest::with('user')
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'tanggal' => $item->start_date . ' s/d ' . $item->end_date,
                            'nama_karyawan' => $item->user ? $item->user->name : 'Unknown',
                            'jam_masuk' => $item->type,
                            'jam_pulang' => $item->reason,
                            'status' => $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Menunggu'),
                            'keterangan' => ''
                        ];
                    });

                return response()->json([
                    'message' => 'Data laporan cuti berhasil diambil',
                    'data' => $data
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan internal server.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // ✅ FITUR BARU: REKAP BULANAN DETAIL PER KARYAWAN
    // ==========================================
    public function getMonthlyDetail(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->level_position, ['Partner', 'Manager'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $bulan = $request->input('bulan', date('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $bulan)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $bulan)->endOfMonth();

        // Ambil semua user aktif (kecuali Partner/Super Admin)
        $users = User::where('status_aktif', 'aktif')
            ->where('level_position', '!=', 'Partner')
            ->with('divisi')
            ->get();

        $result = [];

        foreach ($users as $user) {
            // Absensi user ini di bulan tersebut
            $absensi = Absensi::where('user_id', $user->id)
                ->whereBetween('tanggal', [$start, $end])
                ->orderBy('tanggal', 'asc')
                ->get()
                ->map(function ($a) {
                    return [
                        'tanggal' => $a->tanggal,
                        'jam_masuk' => $a->jam_masuk,
                        'jam_pulang' => $a->jam_pulang,
                        'status' => $a->status,
                        'durasi' => $this->hitungDurasi($a->jam_masuk, $a->jam_pulang),
                    ];
                });

            // Cuti user ini di bulan tersebut
            $cuti = LeaveRequest::where('user_id', $user->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end]);
                })
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($c) {
                    return [
                        'periode' => $c->start_date . ' s/d ' . $c->end_date,
                        'jenis' => $c->type,
                        'alasan' => $c->reason,
                        'status' => $c->status === 'approved' ? 'Disetujui' : 
                                   ($c->status === 'rejected' ? 'Ditolak' : 'Menunggu'),
                    ];
                });

            // Hanya tampilkan jika ada absensi atau cuti
            if ($absensi->isNotEmpty() || $cuti->isNotEmpty()) {
                $result[] = [
                    'karyawan' => [
                        'id' => $user->id,
                        'nama' => $user->name,
                        'email' => $user->email,
                        'divisi' => $user->divisi ? $user->divisi->name : '-',
                    ],
                    'absensi' => $absensi,
                    'cuti' => $cuti,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'bulan' => $bulan,
            'periode' => $start->format('d M Y') . ' - ' . $end->format('d M Y'),
            'data' => $result
        ]);
    }

    // ==========================================
    // FITUR BARU: SYSTEM ANALYSIS — SUPER ADMIN ONLY
    // ==========================================
    public function getSystemAnalysis(): JsonResponse
    {
        $user = Auth::user();
        if ($user->level_position !== 'Partner') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $totalUsers = User::count();
        $totalAbsensi = Absensi::count();
        $totalCuti = LeaveRequest::count();
        $activeTokens = AttendanceToken::where('is_active', true)->count();
        
        $lastError = 'Tidak ada error dalam 24 jam terakhir';
        if (file_exists(storage_path('logs/laravel.log'))) {
            $log = file_get_contents(storage_path('logs/laravel.log'));
            if (strpos($log, 'ERROR') !== false) {
                $lastError = 'Ditemukan error dalam log sistem';
            }
        }

        $serverLoad = 0;
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $serverLoad = $load[0] ?? 0;
        }

        $memoryUsage = round((memory_get_usage(true) / 1024 / 1024), 2);
        $phpVersion = PHP_VERSION;

        return response()->json([
            'message' => 'Data analisis sistem berhasil dimuat',
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'employees' => User::where('level_position', 'Employee')->count(),
                    'managers' => User::where('level_position', 'Manager')->count(),
                    'partners' => User::where('level_position', 'Partner')->count(),
                ],
                'activity' => [
                    'absensi_today' => Absensi::whereDate('tanggal', now()->toDateString())->count(),
                    'cuti_pending' => LeaveRequest::where('status', 'pending')->count(),
                    'total_absensi' => $totalAbsensi,
                    'total_cuti' => $totalCuti,
                ],
                'system' => [
                    'active_tokens' => $activeTokens,
                    'server_load' => round($serverLoad, 2),
                    'memory_usage_mb' => $memoryUsage,
                    'php_version' => $phpVersion,
                    'laravel_version' => app()->version(),
                    'last_error' => $lastError,
                ]
            ]
        ]);
    }

    // ==========================================
    // FITUR BARU: RESET PASSWORD — SUPER ADMIN ONLY
    // ==========================================
    public function resetUserPassword(Request $request, $user_id): JsonResponse
    {
        $currentUser = Auth::user();
        if ($currentUser->level_position !== 'Partner') {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $targetUser = User::find($user_id);
        if (!$targetUser) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }

        if ($targetUser->level_position === 'Partner' && $targetUser->id !== $currentUser->id) {
            return response()->json(['message' => 'Tidak bisa mereset password Super Admin lain.'], 403);
        }

        if (!in_array($targetUser->level_position, ['Employee', 'Manager'])) {
            return response()->json(['message' => 'Hanya karyawan dan admin biasa yang bisa di-reset.'], 400);
        }

        $newPassword = Str::random(8);
        $targetUser->password = Hash::make($newPassword);
        $targetUser->save();

        Notification::create([
            'user_id' => $targetUser->id,
            'title' => 'Password Telah Di-Reset',
            'message' => 'Password Anda telah di-reset oleh Super Admin. Password baru: ' . $newPassword,
            'type' => 'password_reset',
            'is_read' => false,
        ]);

        return response()->json([
            'message' => 'Password berhasil di-reset.',
            'user_email' => $targetUser->email,
            'new_password' => $newPassword
        ]);
    }

    // ==========================================
    // HELPER
    // ==========================================

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