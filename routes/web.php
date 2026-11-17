<?php

use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\TeacherAccountController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\TeacherScheduleController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\PreventPrivatePageCaching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::pattern('schedule', '[0-9]+');
Route::pattern('teacher', '[0-9]+');
Route::get('/', function (Request $request): RedirectResponse {
    return to_route(match (true) {
        $request->user()?->is_admin === true => 'admin.schedules.index',
        $request->user()?->teacher_id !== null => 'teacher.schedule',
        default => 'login',
    });
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/dang-nhap', [AdminSessionController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [AdminSessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/quen-mat-khau', [PasswordController::class, 'forgot'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [PasswordController::class, 'showReset'])
        ->where('token', '[a-f0-9]{64}')->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware(['auth', 'auth.session', PreventPrivatePageCaching::class])->group(function (): void {
    Route::post('/dang-xuat', [AdminSessionController::class, 'destroy'])->name('logout');
    Route::get('/doi-mat-khau', [PasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('/doi-mat-khau', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('account.password.update');

    Route::get('/lich-cua-toi', [TeacherScheduleController::class, 'index'])
        ->middleware([EnsurePasswordChanged::class, 'can:view-own-schedule'])->name('teacher.schedule');

    Route::prefix('admin')->name('admin.')
        ->middleware([EnsurePasswordChanged::class, 'can:manage-schedules'])->group(function (): void {
            Route::get('/giang-vien', [TeacherAccountController::class, 'index'])->name('teachers.index');
            Route::get('/giang-vien/{teacher}/mat-khau', [TeacherAccountController::class, 'edit'])->name('teachers.password.edit');
            Route::put('/giang-vien/{teacher}/mat-khau', [TeacherAccountController::class, 'update'])->name('teachers.password.update');
            Route::get('/lich-day/goi-y', [ScheduleController::class, 'suggestions'])->name('schedules.suggestions');
            Route::get('/lich-day/{schedule}/xoa', [ScheduleController::class, 'confirmDestroy'])
                ->whereNumber('schedule')->name('schedules.confirm-destroy');
            Route::resource('lich-day', ScheduleController::class)->except(['show'])
                ->parameters(['lich-day' => 'schedule'])->names('schedules');
        });
});
