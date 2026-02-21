<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\RegisterController;

// Import Controllers API
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\TaskAttachmentController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\BugReportController;
use App\Http\Controllers\Api\ProjectIssueController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 🔓 PUBLIC ROUTES
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/register/admin', [RegisterController::class, 'registerAdmin']);

// 🔒 PROTECTED ROUTES (auth:sanctum)
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth & User
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/upload-foto', [ProfileController::class, 'uploadFotoProfil']);
    Route::post('/profile/update-personal', [ProfileController::class, 'updatePersonalInfo']);
    Route::post('/user/update-profile', [ProfileController::class, 'updateProfile']);

    // Absensi
    Route::post('/absensi/clock-in', [AbsensiController::class, 'clockIn']);
    Route::post('/absensi/clock-out', [AbsensiController::class, 'clockOut']);
    Route::get('/absensi/riwayat', [AbsensiController::class, 'riwayat']);

    // Notifikasi, Cuti & Dokumen
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::apiResource('leave-request', LeaveRequestController::class)->only(['index', 'store', 'destroy']);
    Route::post('/upload-document', [DocumentController::class, 'uploadDocument']);

    // ✅ DASHBOARD
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ✅ EXPORT CSV (TAHAP 3)
    // Pastikan ini diletakkan SEBELUM Route::apiResource('tasks') agar tidak bentrok
    Route::get('/export-tasks', [TaskController::class, 'exportCsv']);

    // ✅ TASK DETAILS
    Route::get('/tasks/{id}/checklists', [TaskController::class, 'getChecklists']);
    Route::get('/tasks/{id}/comments', [TaskController::class, 'getComments']);
    Route::get('/tasks/{id}/attachments', [TaskController::class, 'getAttachments']);
    
    // ✅ ACTIVITY LOGS (TAHAP 3)
    Route::get('/tasks/{id}/logs', [TaskController::class, 'getLogs']);

    // ✅ TASK CRUD
    Route::apiResource('tasks', TaskController::class);

    // ✅ PROJECT & BOARD
    Route::get('/users/assignable', [ProjectController::class, 'getAssignableUsers']); 
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('boards', BoardController::class)->except(['show']);
    Route::get('/projects/{project}/boards', [BoardController::class, 'index']);

    // ✅ CHECKLIST (CRUD Custom dari TaskController sesuai tahap 2)
    Route::post('/tasks/{taskId}/checklists', [TaskController::class, 'addChecklist']);
    Route::put('/checklists/{checklistId}', [TaskController::class, 'updateChecklist']);
    Route::delete('/checklists/{checklistId}', [TaskController::class, 'deleteChecklist']);
    
    // Resource bawaan (biarkan saja jika sudah ada dari awal)
    Route::apiResource('checklists', ChecklistController::class);

    // ✅ COMMENTS
    Route::apiResource('comments', CommentController::class)->except(['update', 'show']);
    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);

    // ✅ ATTACHMENTS
    Route::post('/attachments', [TaskAttachmentController::class, 'store']);
    Route::get('/tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
    Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

    // ✅ REMINDERS
    Route::post('/reminders/run', [ReminderController::class, 'runReminders']);
    Route::get('/reminders/test', [ReminderController::class, 'testReminders']);

    // ✅ MILESTONES
    Route::apiResource('milestones', MilestoneController::class)->except(['index', 'show']);
    Route::get('/projects/{project}/milestones', [MilestoneController::class, 'index']);

    // ✅ REPORTS
    Route::get('/projects/{project}/report/pdf', [ReportController::class, 'exportProjectPdf']);
    Route::get('/projects/{project}/report', [ReportController::class, 'getProjectReport']);

    // ✅ BUG REPORTS
    Route::apiResource('bugs', BugReportController::class)->except(['show']);
    Route::get('/projects/{project}/bugs', [BugReportController::class, 'index']);

    // ✅ PROJECT ISSUES
    Route::apiResource('project-issues', ProjectIssueController::class)->except(['show']);
    Route::get('/projects/{project}/issues', [ProjectIssueController::class, 'index']);

    // ==========================================
    // ADMIN AREA
    // ==========================================
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard-harian', [AdminController::class, 'dashboardHarian']);
        Route::get('/rekap-semua', [AdminController::class, 'rekapSemua']);
        Route::get('/attendances/today', [AdminController::class, 'getTodayAttendances']);
        Route::get('/laporan/export-pdf', [AdminController::class, 'exportLaporanPDF']);
        Route::get('/laporan/bulanan/export-pdf', [AdminController::class, 'exportLaporanBulananPDF']);
        Route::get('/laporan', [AdminController::class, 'getRekapLaporan']);
        Route::get('/laporan/bulanan', [AdminController::class, 'getMonthlyDetail']);
        Route::get('/leaves', [AdminController::class, 'getLeaveRequests']);
        Route::put('/leaves/{id}', [AdminController::class, 'updateLeaveStatus']);
        Route::post('/generate-token', [AdminController::class, 'generateAttendanceToken']);
        Route::get('/documents', [DocumentController::class, 'index']);
        Route::get('/notifications/all', [NotificationController::class, 'getAllNotifications']);
        Route::get('/karyawan', [AdminController::class, 'getAllemployees']);
        Route::delete('/karyawan/{id}', [AdminController::class, 'deleteEmployee']);
    });

    // ==========================================
    // SUPER ADMIN ONLY
    // ==========================================
    Route::prefix('super-admin')->group(function () {
        Route::post('/karyawan', [AdminController::class, 'addEmployee']);
        Route::get('/system-analysis', [AdminController::class, 'getSystemAnalysis']);
        Route::post('/reset-password/{user_id}', [AdminController::class, 'resetUserPassword']);
    });
});