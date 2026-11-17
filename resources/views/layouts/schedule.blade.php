<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · HUIT Schedule Hub</title>
    <link rel="stylesheet" href="{{ asset('css/schedule.css') }}">
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
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="button button-quiet" type="submit">Đăng xuất</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="shell main" id="noi-dung">
        @if (session('status'))
            <p class="notice notice-success" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
</body>
</html>
