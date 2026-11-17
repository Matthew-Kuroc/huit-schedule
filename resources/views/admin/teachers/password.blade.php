@extends('layouts.schedule')

@section('title', 'Đặt mật khẩu tạm')

@section('content')
    <section class="panel auth-card" aria-labelledby="temporary-password-title">
        <h1 id="temporary-password-title">Đặt mật khẩu tạm</h1>
        <p class="muted">{{ $teacher->full_name }} · {{ $teacher->login_email ?? $teacher->email }}</p>
        <form class="fields" method="POST" action="{{ route('admin.teachers.password.update', $teacher->id) }}">
            @csrf
            @method('PUT')
            @include('auth.password-fields', ['passwordLabel' => 'Mật khẩu tạm'])
            <p class="muted field-hint">Gửi mật khẩu tạm riêng cho giảng viên. Họ sẽ đổi mật khẩu sau khi đăng nhập.</p>
            <div class="actions">
                <button class="button button-primary" type="submit">Lưu mật khẩu tạm</button>
                <a class="button button-secondary" href="{{ route('admin.teachers.index') }}">Hủy</a>
            </div>
        </form>
    </section>
@endsection
