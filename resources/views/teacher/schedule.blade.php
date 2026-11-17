@extends('layouts.schedule')

@section('title', 'Lịch của tôi')

@section('content')
    <header class="page-heading">
        <div>
            <h1>Lịch của tôi</h1>
            <p class="muted">{{ $teacher->full_name }} · {{ $teacher->expertise }}</p>
        </div>
        <nav class="actions" aria-label="Chế độ xem lịch">
            @foreach (['week' => 'Theo tuần', 'month' => 'Theo tháng'] as $key => $label)
                <a class="button {{ $mode === $key ? 'button-primary' : 'button-secondary' }}"
                   href="{{ route('teacher.schedule', ['view' => $key, 'date' => $date]) }}"
                   @if ($mode === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <section class="panel personal-schedule" aria-labelledby="schedule-range">
        <div class="section-heading calendar-head">
            <h2 id="schedule-range">{{ $rangeLabel }}</h2>
            <nav class="actions" aria-label="Khoảng ngày">
                <a class="button button-secondary" rel="prev" href="{{ route('teacher.schedule', ['view' => $mode, 'date' => $previous]) }}">{{ $mode === 'week' ? 'Tuần trước' : 'Tháng trước' }}</a>
                <a class="button button-quiet" href="{{ route('teacher.schedule', ['view' => $mode]) }}">Hôm nay</a>
                <a class="button button-secondary" rel="next" href="{{ route('teacher.schedule', ['view' => $mode, 'date' => $next]) }}">{{ $mode === 'week' ? 'Tuần sau' : 'Tháng sau' }}</a>
            </nav>
        </div>
        <p class="calendar-note muted">Lịch dạy lặp theo thứ trong tuần. Chế độ tháng hiển thị những ngày có lớp.</p>

        <div class="agenda">
            @forelse ($days as $day)
                <section class="agenda-day">
                    <h3>
                        {{ $day['name'] }}
                        <time datetime="{{ $day['date']->toDateString() }}">{{ $day['date']->format('d/m') }}</time>
                        @if ($day['date']->toDateString() === $today)<span class="period">Hôm nay</span>@endif
                    </h3>
                    @if ($day['lessons']->isEmpty())
                        <p class="muted">Không có lịch dạy.</p>
                    @else
                        <ul class="agenda-lessons">
                            @foreach ($day['lessons'] as $lesson)
                                <li>
                                    <span class="period">{{ \App\Support\ScheduleTime::label($lesson->shift_or_periods) }}</span>
                                    <div>
                                        <strong>{{ $lesson->subject_name }}</strong>
                                        <p>{{ $lesson->branch_name }} · Phòng {{ $lesson->room }}</p>
                                        <p class="muted">{{ $lesson->address }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @empty
                <p class="empty">Không có lịch dạy trong tháng này.</p>
            @endforelse
        </div>
    </section>
@endsection
