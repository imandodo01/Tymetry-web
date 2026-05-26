<?php

use App\Http\Controllers\Web\Auth\AuthController;
use App\Http\Controllers\Web\Auth\OtpController;
use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\Todo\TodoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index']);
Route::get('/', [AuthController::class, 'index'])->name('login');

Route::get('/sample', function () {
    return view('sample');
});

Route::controller(AuthController::class)->name('auth.')->group(function () {
    Route::get('/login', 'index')->name('index');
    Route::post('/login', 'login')->name('login');
    Route::post('/logout', 'logout')->name('logout')->middleware('auth');
});

Route::controller(ForgotPasswordController::class)->name('password.')->group(function () {
    Route::get('/forgot-password', 'forgotPassword')->name('index');
    Route::post('/forgot-password', 'sendResetLink')->name('resetLink');

    Route::get('/reset-password/{token}', 'resetPassword')->name('reset');
    Route::post('/reset-password', 'updatePassword')->name('update');
});

Route::controller(OtpController::class)->name('otp.')->group(function () {
    Route::get('/otp', 'index')->name('index');
    Route::post('/send', 'send')->name('send');
    Route::post('/verify', 'verify')->name('verify');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
    });

    Route::controller(TodoController::class)->prefix('todos')->name('todo.')->group(function () {
        Route::get('/', 'index')->name('index');

        Route::post('/bulk/done', 'bulkDone')->name('bulk.done');
        Route::post('/bulk/archive', 'bulkArchive')->name('bulk.archive');
        Route::post('/bulk/restore', 'bulkRestore')->name('bulk.restore');

        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');

        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');

        Route::delete('/{id}', 'destroy')->name('destroy');

        Route::patch('/{id}/priority', 'updatePriority')->name('priority');
        Route::post('/{id}/status', 'updateStatus')->name('status');
        Route::post('/{id}/complete', 'toggleComplete')->name('complete');
        Route::patch('/{id}/due-date', 'updateDueDate')->name('due-date');

        Route::get('/archive', 'archive')->name('archive');
        Route::post('/{id}/restore', 'restore')->name('restore');
    });

    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {

        Route::get('/', 'index')->name('index');

        Route::put('/update', 'update')->name('update');

        Route::put('/password', 'password')->name('password');
    });
});
