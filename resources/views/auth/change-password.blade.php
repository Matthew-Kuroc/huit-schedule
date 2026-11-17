@extends('layouts.schedule')

@section('title', 'Đổi mật khẩu')

@section('content')
    <section class="panel auth-card" aria-labelledby="password-title">
        <h1 id="password-title">Đổi mật khẩu</h1>
        @if (auth()->user()->password_state === \App\Models\User::PASSWORD_TEMPORARY)
            <p class="muted">Bạn đang dùng mật khẩu tạm. Hãy đặt mật khẩu riêng để tiếp tục.</p>
        @else
            <p class="muted">{{ auth()->user()->email }}</p>
        @endif
        <form class="fields" method="POST" action="{{ route('account.password.update') }}">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="current_password">Mật khẩu hiện tại</label>
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                       @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror>
                @error('current_password')<p class="field-error" id="current-password-error" role="alert">{{ $message }}</p>@enderror
            </div>
            @include('auth.password-fields')
            <button class="button button-primary" type="submit">Lưu mật khẩu mới</button>
        </form>
    </section>
@endsection
