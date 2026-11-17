@extends('layouts.schedule')

@section('title', 'Đăng nhập')

@section('content')
    <section class="panel auth-card" aria-labelledby="login-title">
        <h1 id="login-title">Đăng nhập</h1>
        <p class="muted">Dùng tài khoản quản trị hoặc tài khoản giảng viên được cấp.</p>

        <form class="fields" action="{{ route('login.store') }}" method="POST">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')<p class="field-error" id="password-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="button button-primary" type="submit">Đăng nhập</button>
            <a class="back-link" href="{{ route('password.request') }}">Quên mật khẩu?</a>
        </form>
    </section>
@endsection
