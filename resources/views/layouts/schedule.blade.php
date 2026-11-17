<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · HUIT Schedule Hub</title>
    <link rel="stylesheet" href="{{ asset('css/schedule.css') }}">
    @auth
        <script defer src="{{ asset('js/private-history.js') }}"></script>
    @endauth
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#noi-dung">Tới nội dung</a>
    <header class="topbar">
        <div class="shell topbar-inner">
            <a class="brand" href="{{ url('/') }}">
                <span class="brand-mark">HUIT</span>
                <span>Schedule Hub</span>
            </a>
            @auth
                <nav class="actions account-nav" aria-label="Tài khoản">
                    @if (auth()->user()->password_state !== \App\Models\User::PASSWORD_TEMPORARY)
                        @can('manage-schedules')
                            <a class="button button-quiet" href="{{ route('admin.schedules.index') }}" @if (request()->routeIs('admin.schedules.*')) aria-current="page" @endif>Lịch dạy</a>
                            <a class="button button-quiet" href="{{ route('admin.teachers.index') }}" @if (request()->routeIs('admin.teachers.*')) aria-current="page" @endif>Giảng viên</a>
                        @endcan
                    @endif
                    <a class="button button-quiet" href="{{ route('account.password.edit') }}" @if (request()->routeIs('account.password.*')) aria-current="page" @endif>Đổi mật khẩu</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="button button-quiet" type="submit">Đăng xuất</button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="shell main @yield('main-class')" id="noi-dung">
        @if (session('status'))
            <p class="notice notice-success" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
