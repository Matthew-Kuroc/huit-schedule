@extends('layouts.schedule')

@section('title', 'Quên mật khẩu')

@section('content')
    <section class="panel auth-card" aria-labelledby="forgot-title">
        <h1 id="forgot-title">Quên mật khẩu</h1>
        <p class="muted">Nhập email đăng nhập để nhận liên kết đặt lại mật khẩu.</p>
        <form class="fields" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <button class="button button-primary" type="submit">Gửi liên kết đặt lại</button>
            <a class="back-link" href="{{ route('login') }}">Về trang đăng nhập</a>
        </form>
    </section>
@endsection
