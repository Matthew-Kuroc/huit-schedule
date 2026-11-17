<div class="calendar-month">
    @foreach ($weekdays as $weekday)
        <div class="month-weekday">{{ $weekday }}</div>
    @endforeach

    @for ($placeholder = 0; $placeholder < $monthOffset; $placeholder++)
        <div class="month-placeholder" aria-hidden="true"></div>
    @endfor

    @foreach ($days as $day)
        <section class="month-day {{ $day['isToday'] ? 'calendar-is-today' : '' }} {{ $day['lessons']->isNotEmpty() ? 'month-has-lessons' : '' }}"
                 aria-label="{{ $day['name'] }}, {{ $day['date']->format('d/m/Y') }}">
            <div class="month-date">
                <time datetime="{{ $day['date']->toDateString() }}" @if ($day['isToday']) aria-current="date" @endif>{{ $day['date']->day }}</time>
                @if ($day['lessons']->isNotEmpty())
                    <span class="month-day-count">{{ $day['lessons']->count() }} buổi</span>
                @endif
            </div>
            @foreach ($day['lessons'] as $lesson)
                @include('teacher.schedule-lesson')
            @endforeach
        </section>
    @endforeach

    @for ($placeholder = 0; $placeholder < $monthTrailingDays; $placeholder++)
        <div class="month-placeholder" aria-hidden="true"></div>
    @endfor
</div>
