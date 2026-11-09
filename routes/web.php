<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Modules\Personnel\Controllers\PersonnelController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard.index');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    Route::get('/personnel/change-requests', [PersonnelController::class, 'changeRequests'])
        ->middleware(EnsureUserIsAdmin::class)
        ->name('personnel.change-requests.index');
    Route::post('/personnel/change-requests/{changeRequest}/approve', [PersonnelController::class, 'approveChangeRequest'])
        ->middleware(EnsureUserIsAdmin::class)
        ->name('personnel.change-requests.approve');
    Route::post('/personnel/change-requests/{changeRequest}/reject', [PersonnelController::class, 'rejectChangeRequest'])
        ->middleware(EnsureUserIsAdmin::class)
        ->name('personnel.change-requests.reject');

    Route::post('/personnel/{personnel}/status', [PersonnelController::class, 'changeStatus'])->name('personnel.status');
    Route::post('/personnel/{personnel}/mutate', [PersonnelController::class, 'mutate'])->name('personnel.mutate');
    Route::resource('personnel', PersonnelController::class);
});
