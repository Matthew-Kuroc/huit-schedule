<?php

use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\AdminSessionController;
use App\Http\Controllers\TeacherScheduleController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::pattern('schedule', '[0-9]+');
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
});
Route::post('/dang-xuat', [AdminSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/lich-cua-toi', [TeacherScheduleController::class, 'index'])
    ->middleware(['auth', 'can:view-own-schedule'])->name('teacher.schedule');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:manage-schedules'])->group(function (): void {
    Route::get('/lich-day/goi-y', [ScheduleController::class, 'suggestions'])->name('schedules.suggestions');
    Route::get('/lich-day/{schedule}/xoa', [ScheduleController::class, 'confirmDestroy'])
        ->whereNumber('schedule')->name('schedules.confirm-destroy');
    Route::resource('lich-day', ScheduleController::class)->except(['show'])
        ->parameters(['lich-day' => 'schedule'])->names('schedules');
});
