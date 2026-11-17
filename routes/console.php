<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('schedule:create-admin {email} {--generate-password}', function (): int {
    $email = Str::lower(trim($this->argument('email')));

    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $this->error('Email chưa đúng định dạng.');

        return 1;
    }

    if (User::where('email', $email)->exists()) {
        $this->error('Email này đã có tài khoản.');

        return 1;
    }

    $password = $this->option('generate-password') ? Str::random(20) : $this->secret('Mật khẩu (ít nhất 10 ký tự)');

    if (! is_string($password) || strlen($password) < 10 || strlen($password) > 72) {
        $this->error('Mật khẩu phải dài từ 10 đến 72 byte.');

        return 1;
    }

    $user = new User(['name' => 'Quản trị viên', 'email' => $email, 'password' => $password]);
    $user->is_admin = true;
    $user->save();

    $this->info('Đã tạo tài khoản quản trị.');

    if ($this->option('generate-password')) {
        $this->line("Mật khẩu vừa tạo: $password");
    }

    return 0;
})->purpose('Tạo tài khoản quản trị lịch dạy');

Artisan::command('schedule:create-teacher {teacher} {--generate-password}', function (): int {
    $teacher = DB::table('teachers')->find($this->argument('teacher'));
    if ($teacher === null) {
        $this->error('Không tìm thấy giảng viên theo ID.');

        return 1;
    }

    if (User::where('teacher_id', $teacher->id)->orWhere('email', $teacher->email)->exists()) {
        $this->error('Giảng viên hoặc email này đã có tài khoản.');

        return 1;
    }

    $password = $this->option('generate-password') ? Str::random(20) : $this->secret('Mật khẩu (ít nhất 10 ký tự)');
    if (! is_string($password) || strlen($password) < 10 || strlen($password) > 72) {
        $this->error('Mật khẩu phải dài từ 10 đến 72 byte.');

        return 1;
    }

    $user = new User(['name' => $teacher->full_name, 'email' => $teacher->email, 'password' => $password]);
    $user->teacher_id = $teacher->id;
    $user->save();

    $this->info("Đã tạo tài khoản giảng viên $teacher->email.");
    if ($this->option('generate-password')) {
        $this->line("Mật khẩu vừa tạo: $password");
    }

    return 0;
})->purpose('Tạo tài khoản đăng nhập cho một giảng viên theo ID');
