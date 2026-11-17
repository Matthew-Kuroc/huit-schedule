@extends('layouts.schedule')

@section('title', 'Xóa lịch dạy')

@section('content')
    <section class="panel auth-card" aria-labelledby="delete-title">
        <h1 id="delete-title">Xóa lịch dạy?</h1>
        <dl class="record">
            <dt>Học phần</dt><dd>{{ $schedule->subject_name }}</dd>
            <dt>Giảng viên</dt><dd>{{ $schedule->full_name }}</dd>
            <dt>Ngày và tiết</dt><dd>{{ $days[$schedule->day_of_week] ?? 'Chưa rõ' }} · {{ \App\Support\ScheduleTime::label($schedule->shift_or_periods) }}</dd>
            <dt>Cơ sở và phòng</dt><dd>{{ $schedule->branch_name }} · {{ $schedule->room }}</dd>
        </dl>
        <form method="POST" action="{{ route('admin.schedules.destroy', $schedule->id) }}" class="actions">
            @csrf
            @method('DELETE')
            <a class="button button-secondary" href="{{ route('admin.schedules.index') }}">Giữ lịch</a>
            <button class="button button-danger" type="submit">Xóa lịch dạy</button>
        </form>
    </section>
@endsection
