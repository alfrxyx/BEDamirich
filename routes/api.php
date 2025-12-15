<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\NotificationController; 

/*
|--------------------------------------------------------------------------
| API Routes (Lengkap)
|--------------------------------------------------------------------------
*/

// 1. PUBLIC
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);

// 2. PROTECTED
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/admin/laporan', [AdminController::class, 'getRekapLaporan']);
    
    // --- KARYAWAN ---
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

    Route::post('/absensi/clock-in', [AbsensiController::class, 'clockIn']);
    Route::post('/absensi/clock-out', [AbsensiController::class, 'clockOut']);
    Route::get('/absensi/riwayat', [AbsensiController::class, 'riwayat']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::apiResource('leave-request', LeaveRequestController::class)->only(['index', 'store', 'destroy']);


    // --- ADMIN AREA ---
    Route::prefix('admin')->group(function () { 
        
        // Dashboard & QR
        Route::get('/dashboard-harian', [AdminController::class, 'dashboardHarian']);
        Route::get('/rekap-semua', [AdminController::class, 'rekapSemua']);
        Route::post('/generate-token', [AdminController::class, 'generateAttendanceToken']);

        // Fitur Approval Cuti
        Route::get('/leaves', [AdminController::class, 'getLeaveRequests']);      
        Route::put('/leaves/{id}', [AdminController::class, 'updateLeaveStatus']); 

        // Manajemen Karyawan
        Route::get('/karyawan', [AdminController::class, 'getAllemployees']);
        Route::post('/karyawan', [AdminController::class, 'addEmployee']);
        Route::delete('/karyawan/{id}', [AdminController::class, 'deleteEmployee']);

        // ⭐ TAMBAHKAN INI - Absensi Hari Ini
        Route::get('/attendances/today', [AdminController::class, 'getTodayAttendances']);
        
        Route::get('/laporan/export-pdf', [AdminController::class, 'exportLaporanPDF']);    
    });
});