<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveRequestController; 

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

    // --- KARYAWAN ---
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

    Route::post('/absensi/clock-in', [AbsensiController::class, 'clockIn']);
    Route::post('/absensi/clock-out', [AbsensiController::class, 'clockOut']);
    Route::get('/absensi/riwayat', [AbsensiController::class, 'riwayat']);

    Route::apiResource('leave-request', LeaveRequestController::class)->only(['index', 'store', 'destroy']);


    // --- ADMIN AREA ---
    Route::prefix('admin')->group(function () { 
        
        // Dashboard & QR
        Route::get('/dashboard-harian', [AdminController::class, 'dashboardHarian']);
        Route::get('/rekap-semua', [AdminController::class, 'rekapSemua']);
        Route::post('/generate-token', [AdminController::class, 'generateAttendanceToken']);

        // Fitur Approval Cuti (BARU)
        Route::get('/leaves', [AdminController::class, 'getLeaveRequests']);      // Lihat daftar
        Route::put('/leaves/{id}', [AdminController::class, 'updateLeaveStatus']); // Setujui/Tolak
        
    });

});