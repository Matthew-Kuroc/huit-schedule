@php
    $weekLessons = collect($days)->flatMap(fn ($day) => $day['lessons']);
    $maxLanes = $weekLessons->max('laneCount') ?? 1;
@endphp

<div class="calendar-week" style="--period-count: {{ count($periods) }}; --max-lanes: {{ $maxLanes }}">
    <div class="week-rail">
        <div class="week-rail-title">Tiết học</div>
        <ol class="week-periods">
            @foreach ($periods as $period)
                <li>Tiết {{ $period }}</li>
            @endforeach
        </ol>
    </div>
    @foreach ($days as $day)
        <section class="week-day {{ $day['isToday'] ? 'calendar-is-today' : '' }}" aria-labelledby="week-day-{{ $loop->iteration }}">
            <div class="week-day-title">
                <h3 id="week-day-{{ $loop->iteration }}">{{ $day['name'] }}</h3>
                <time datetime="{{ $day['date']->toDateString() }}" @if ($day['isToday']) aria-current="date" @endif>{{ $day['date']->format('d/m') }}</time>
                @if ($day['isToday'])<span class="calendar-today-tag">Hôm nay</span>@endif
                @if ($day['lessons']->max('laneCount') > 1)<span class="calendar-conflict-tag">Trùng tiết</span>@endif
            </div>
            <div class="week-day-track">
                @foreach ($day['lessons']->whereNotNull('periods') as $lesson)
                    <div class="week-lesson-slot"
                         @if ($lesson->periods[0] === $lesson->periods[1]) data-single-period @endif
                         @if ($lesson->laneCount > 1) data-shared-period @endif
                         style="--start: {{ $lesson->periods[0] }}; --length: {{ $lesson->periods[1] - $lesson->periods[0] + 1 }}; --lane: {{ $lesson->lane }}; --lane-count: {{ $lesson->laneCount }}">
                        @include('teacher.schedule-lesson')
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>

@if ($weekLessons->whereNull('periods')->isNotEmpty())
    <section class="calendar-unplaced" aria-labelledby="calendar-unplaced-heading">
        <h3 id="calendar-unplaced-heading">Buổi dạy chưa có số tiết chuẩn</h3>
        @foreach ($days as $day)
            @foreach ($day['lessons']->whereNull('periods') as $lesson)
                <div>
                    <p class="muted">{{ $day['name'] }} · {{ $day['date']->format('d/m') }}</p>
                    @include('teacher.schedule-lesson')
                </div>
            @endforeach
        @endforeach
    </section>
@endif
