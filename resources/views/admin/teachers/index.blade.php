@extends('layouts.schedule')

@section('title', 'Tài khoản giảng viên')

@section('content')
    <header class="page-heading">
        <div>
            <h1>Tài khoản giảng viên</h1>
            <p class="muted">Cấp mật khẩu tạm và xem trạng thái tài khoản.</p>
        </div>
    </header>
    <section class="panel filter-panel" aria-label="Tìm giảng viên">
        <form method="GET" action="{{ route('admin.teachers.index') }}" class="teacher-search">
            <div class="field">
                <label for="q">Họ tên hoặc email</label>
                <input id="q" name="q" type="search" value="{{ $search }}" maxlength="100">
            </div>
            <div class="actions">
                <button class="button button-secondary" type="submit">Tìm giảng viên</button>
                @if ($search !== '')
                    <a class="button button-quiet" href="{{ route('admin.teachers.index') }}">Bỏ lọc</a>
                @endif
            </div>
        </form>
    </section>
    <section class="panel schedule-panel" aria-labelledby="teacher-list">
        <div class="section-heading">
            <h2 id="teacher-list">Danh sách giảng viên</h2>
            <p>{{ $teachers->total() }} giảng viên</p>
        </div>
        @if ($teachers->isEmpty())
            <p class="empty">Không tìm thấy giảng viên.</p>
        @else
            <div class="schedule-table teacher-table">
                <table role="table" aria-labelledby="teacher-list">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th role="columnheader" scope="col">Giảng viên</th>
                            <th role="columnheader" scope="col">Email đăng nhập</th>
                            <th role="columnheader" scope="col">Trạng thái</th>
                            <th role="columnheader" scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        @foreach ($teachers as $teacher)
                            <tr role="row">
                                <td role="cell"><strong>{{ $teacher->full_name }}</strong><small>{{ $teacher->expertise }}</small></td>
                                <td role="cell">{{ $teacher->login_email ?? $teacher->email }}</td>
                                <td role="cell">
                                    @if ($teacher->password_state === \App\Models\User::PASSWORD_ACTIVE)
                                        <span class="period">Đã kích hoạt</span>
                                    @elseif ($teacher->password_state === \App\Models\User::PASSWORD_TEMPORARY)
                                        <span class="period">Chờ đổi mật khẩu</span>
                                    @else
                                        <span class="muted">Chờ cấp mật khẩu</span>
                                    @endif
                                </td>
                                <td role="cell">
                                    <a class="button button-secondary" href="{{ route('admin.teachers.password.edit', $teacher->id) }}"
                                       aria-label="Đặt mật khẩu tạm cho {{ $teacher->full_name }}">Đặt mật khẩu tạm</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="paginator">
                <p>Hiển thị {{ $teachers->firstItem() }}–{{ $teachers->lastItem() }} / {{ $teachers->total() }}</p>
                @if ($teachers->hasPages())
                    <nav class="actions" aria-label="Phân trang">
                        @if ($teachers->previousPageUrl())
                            <a class="button button-secondary" rel="prev" href="{{ $teachers->previousPageUrl() }}">Trang trước</a>
                        @endif
                        @if ($teachers->hasMorePages())
                            <a class="button button-secondary" rel="next" href="{{ $teachers->nextPageUrl() }}">Trang sau</a>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </section>
@endsection
