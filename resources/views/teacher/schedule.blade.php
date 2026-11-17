@extends('layouts.schedule')

@section('title', 'Lịch của tôi')
@section('main-class', 'teacher-calendar-main')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/teacher-calendar.css') }}">
    <script defer src="{{ asset('js/teacher-calendar.js') }}"></script>
@endpush

@section('content')
    <header class="teacher-calendar-heading">
        <div>
            <h1>Lịch của tôi</h1>
            <p><strong>{{ $teacher->full_name }}</strong>@if ($teacher->expertise)<span> · {{ $teacher->expertise }}</span>@endif</p>
        </div>
        <nav class="calendar-modes" aria-label="Chế độ xem lịch">
            @foreach (['week' => 'Theo tuần', 'month' => 'Theo tháng'] as $key => $label)
                <a class="button"
                   href="{{ route('teacher.schedule', ['view' => $key, 'date' => $date]) }}"
                   @if ($mode === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <section class="teacher-calendar" aria-labelledby="schedule-range">
        <div class="panel calendar-toolbar">
            <div class="calendar-range">
                <h2 id="schedule-range">{{ $rangeLabel }}</h2>
                <p class="muted">{{ $lessonCount }} buổi dạy</p>
            </div>
            <div class="calendar-controls">
                <nav class="calendar-navigation" aria-label="Điều hướng lịch">
                    <a class="button" rel="prev" href="{{ route('teacher.schedule', ['view' => $mode, 'date' => $previous]) }}"><span aria-hidden="true">‹</span> Trước</a>
                    <a class="button calendar-today-link" href="{{ route('teacher.schedule', ['view' => $mode, 'date' => $today]) }}">Hôm nay</a>
                    <a class="button" rel="next" href="{{ route('teacher.schedule', ['view' => $mode, 'date' => $next]) }}">Sau <span aria-hidden="true">›</span></a>
                </nav>
                <form class="calendar-date" action="{{ route('teacher.schedule') }}" method="GET">
                    <input type="hidden" name="view" value="{{ $mode }}">
                    <label for="calendar-date">Chọn ngày</label>
                    <input id="calendar-date" type="date" name="date" value="{{ $date }}" required>
                    <button class="button button-secondary" type="submit">Xem</button>
                </form>
            </div>
        </div>

        @if ($lessonCount === 0)
            <p class="calendar-message" role="status">Không có lịch dạy trong {{ $mode === 'week' ? 'tuần' : 'tháng' }} này.</p>
        @endif

        <p class="calendar-scroll-hint muted">Vuốt ngang để xem các ngày còn lại.</p>
        <div class="panel calendar-scroll" role="region" aria-label="{{ $mode === 'week' ? 'Thời khóa biểu tuần' : 'Lịch dạy tháng' }}" tabindex="0">
            @if ($mode === 'week')
                @include('teacher.schedule-week')
            @else
                @include('teacher.schedule-month')
            @endif
        </div>

        <dialog id="lesson-details" class="panel lesson-dialog" aria-labelledby="lesson-title">
            <p class="lesson-dialog-date" id="lesson-date"></p>
            <h2 id="lesson-title"></h2>
            <dl class="record">
                <dt>Khung tiết</dt><dd id="lesson-period"></dd>
                <dt>Phòng</dt><dd id="lesson-room"></dd>
                <dt>Cơ sở</dt><dd id="lesson-branch"></dd>
                <dt>Địa chỉ</dt><dd id="lesson-address"></dd>
            </dl>
            <form method="dialog">
                <button class="button button-primary" autofocus>Đóng</button>
            </form>
        </dialog>
    </section>
@endsection
