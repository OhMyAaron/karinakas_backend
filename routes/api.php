<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KegiatanController;
use App\Http\Controllers\RelawanController;
use App\Http\Controllers\DonasiController;
use App\Http\Controllers\KampanyeController;
use App\Http\Controllers\GaleriController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotifikasiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// PUBLIC ROUTES (F1-F4: Halaman Umum tanpa login)
Route::prefix('v1')->group(function () {
    
    // AUTH (F5, F7-F10)
    // Pembatas laju bernama, didaftarkan di AppServiceProvider (NFR 3.4.4.5).
    // Tiap kelompok punya jatah sendiri per alamat IP:
    //   masuk       : 5 / menit  — menutup celah penebakan kata sandi
    //   lupa-sandi  : 5 / menit  — mencegah pengiriman tautan beruntun
    //   reset-sandi : 20 / menit — tautan sudah dijaga token 64 karakter, jadi
    //                 pembatasan di sini hanya menahan penyalahgunaan; jatah
    //                 terpisah agar memuat ulang halaman tidak memblokir login
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:masuk');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:masuk');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:lupa-sandi');
    Route::post('/reset-password/check', [AuthController::class, 'checkResetToken'])->middleware('throttle:reset-sandi');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:reset-sandi');
    
    // KEGIATAN - Public (F4, F22, F40, F41, F53, F54)
    Route::get('/kegiatan', [KegiatanController::class, 'index']);
    Route::get('/kegiatan/{id}', [KegiatanController::class, 'show']);
    Route::get('/kegiatan/available/volunteer', [KegiatanController::class, 'availableForVolunteer']);
    
    // KAMPANYE - Public (F3, F17, F42, F43)
    Route::get('/kampanye', [KampanyeController::class, 'index']);
    Route::get('/kampanye/{id}', [KampanyeController::class, 'show']);
    Route::get('/kampanye/kategori/{kategori}', [KampanyeController::class, 'byKategori']);
    Route::get('/kampanye/recent/list', [KampanyeController::class, 'recent']);
    
    // GALERI - Public (F33, F44)
    Route::get('/galeri', [GaleriController::class, 'index']);
    Route::get('/galeri/kegiatan/{kegiatanId}', [GaleriController::class, 'byKegiatan']);
    Route::get('/galeri/latest/list', [GaleriController::class, 'latest']);
});

// AUTHENTICATED ROUTES
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // AUTH (F7, F8, F11, F35)
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // RELAWAN - User Terdaftar (F13, F55-F57)
    Route::post('/relawan', [RelawanController::class, 'store']);
    Route::get('/relawan/my-registrations', [RelawanController::class, 'myRegistrations']);
    Route::get('/notifikasi/my-notifications', [NotifikasiController::class, 'myNotifications']);
    Route::get('/relawan/{id}/status', [RelawanController::class, 'status']);
    Route::delete('/relawan/{id}/cancel', [RelawanController::class, 'cancel']);
    
    // DONASI - User Terdaftar (F12, F24)
    Route::post('/donasi', [DonasiController::class, 'store']);
    Route::post('/donasi/{id}/bukti', [DonasiController::class, 'uploadBukti']);
    Route::get('/donasi/my-donations', [DonasiController::class, 'myDonations']);
    
    // ADMIN & SUPER_ADMIN ROUTES
    Route::middleware('role:admin,super_admin')->group(function () {
        
        // DASHBOARD (Dashboard statistik sesuai role)
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
        
        // KEGIATAN CRUD (F19-F23, F80)
        Route::post('/admin/kegiatan', [KegiatanController::class, 'store']);
        Route::put('/admin/kegiatan/{id}', [KegiatanController::class, 'update']);
        Route::delete('/admin/kegiatan/{id}', [KegiatanController::class, 'destroy']);
        Route::get('/admin/kegiatan/search', [KegiatanController::class, 'search']);
        Route::patch('/admin/kegiatan/{id}/status', [KegiatanController::class, 'updateStatus']);
        
        // KAMPANYE CRUD (F14-F18)
        Route::post('/admin/kampanye', [KampanyeController::class, 'store']);
        Route::put('/admin/kampanye/{id}', [KampanyeController::class, 'update']);
        Route::delete('/admin/kampanye/{id}', [KampanyeController::class, 'destroy']);
        Route::get('/admin/kampanye/search', [KampanyeController::class, 'search']);
        
        // DONASI CRUD (F25-F28, F60, F79)
        Route::get('/admin/donasi', [DonasiController::class, 'index']);
        Route::put('/admin/donasi/{id}', [DonasiController::class, 'update']);
        Route::delete('/admin/donasi/{id}', [DonasiController::class, 'destroy']);
        Route::get('/admin/donasi/search', [DonasiController::class, 'search']);
        Route::get('/admin/donasi/report', [DonasiController::class, 'report']);
        Route::patch('/admin/donasi/{id}/status', [DonasiController::class, 'updateStatus']);
        
        // GALERI CRUD (F29-F33)
        Route::post('/admin/galeri/upload', [GaleriController::class, 'bulkUpload']);
        Route::post('/admin/galeri', [GaleriController::class, 'store']);
        Route::put('/admin/galeri/{id}', [GaleriController::class, 'update']);
        Route::delete('/admin/galeri/{id}', [GaleriController::class, 'destroy']);
        Route::get('/admin/galeri/search', [GaleriController::class, 'search']);
        
        // RELAWAN MANAGEMENT (F81)
        Route::get('/admin/relawan', [RelawanController::class, 'index']);
        Route::get('/admin/relawan/pending', [RelawanController::class, 'pending']);
        Route::get('/admin/relawan/kegiatan/{kegiatanId}', [RelawanController::class, 'byKegiatan']);
        Route::patch('/admin/relawan/{id}/status', [RelawanController::class, 'updateStatus']);
        Route::delete('/admin/relawan/{id}', [RelawanController::class, 'destroy']);
        
        // NOTIFIKASI & AUDIT LOG
        Route::get('/admin/notifikasi', [AdminController::class, 'notifikasiLogs']);
        Route::get('/admin/notifikasi/template', [NotifikasiController::class, 'templates']);
        Route::put('/admin/notifikasi/template', [NotifikasiController::class, 'simpanTemplates']);
        Route::get('/admin/audit', [AdminController::class, 'auditLogs']);

        // USERS MANAGEMENT (F36-F38)
        Route::get('/admin/users', [AdminController::class, 'users']);
        Route::post('/admin/users', [AdminController::class, 'storeUser']);
        Route::put('/admin/users/{id}', [AdminController::class, 'updateUser']);
        Route::post('/admin/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        
        // REKAPAN DATA (F59-F61, F63-F65)
        Route::get('/admin/rekapan/relawan', [AdminController::class, 'rekapanRelawan']);
        Route::get('/admin/rekapan/kegiatan', [AdminController::class, 'rekapanKegiatan']);
        Route::get('/admin/rekapan/donasi', [AdminController::class, 'rekapanDonasi']);
        Route::post('/admin/rekapan/export', [AdminController::class, 'exportRekapan']);
    });
});

// WEBHOOK ROUTES (untuk payment gateway, notifikasi, dll)
Route::prefix('v1/webhook')->group(function () {
    // Payment gateway callback
    Route::post('/payment/callback', [DonasiController::class, 'updateStatus']);
    
    // Notification triggers
    Route::post('/notifikasi/donasi/{donasiId}', [NotifikasiController::class, 'kirimNotifikasiDonasi']);
});