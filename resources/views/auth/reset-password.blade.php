@extends('layouts.schedule')

@section('title', 'Đặt lại mật khẩu')

@section('content')
    <section class="panel auth-card" aria-labelledby="reset-title">
        <h1 id="reset-title">Đặt lại mật khẩu</h1>
        <form class="fields" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="username" required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<p class="field-error" id="email-error" role="alert">{{ $message }}</p>@enderror
                @error('token')<p class="field-error" role="alert">Liên kết đặt lại không hợp lệ.</p>@enderror
            </div>
            @include('auth.password-fields')
            <button class="button button-primary" type="submit">Đặt lại mật khẩu</button>
            <a class="back-link" href="{{ route('password.request') }}">Yêu cầu liên kết mới</a>
        </form>
    </section>
@endsection
