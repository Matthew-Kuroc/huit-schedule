<button type="button" class="calendar-lesson lesson-color-{{ $lesson->color }}" aria-haspopup="dialog"
        data-subject="{{ $lesson->subject_name }}"
        data-date="{{ $day['name'] }}, {{ $day['date']->format('d/m/Y') }}"
        data-period="{{ $lesson->periodLabel }}"
        data-room="{{ $lesson->room }}"
        data-branch="{{ $lesson->branch_name }}"
        data-address="{{ $lesson->address }}"
        aria-label="{{ $lesson->subject_name }}, {{ $day['name'] }} {{ $day['date']->format('d/m') }}, {{ $lesson->periodLabel }}, phòng {{ $lesson->room }}, {{ $lesson->branch_name }}">
    <strong class="lesson-subject">{{ $lesson->subject_name }}</strong>
    <span class="lesson-meta">
        <span>{{ $lesson->periodLabel }}</span>
        <span class="lesson-meta-divider" aria-hidden="true">·</span>
        <span><span class="lesson-room-label">Phòng </span>{{ $lesson->room }}</span>
    </span>
    <span class="lesson-branch">{{ $lesson->branch_name }}</span>
</button>
