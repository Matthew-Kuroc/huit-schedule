@extends('layouts.schedule')

@section('title', 'Lịch dạy')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Lịch dạy</h1>
            <p class="muted">Thời khóa biểu theo tuần của các cơ sở.</p>
        </div>
        <a class="button button-primary" href="{{ route('admin.schedules.create') }}">Thêm lịch dạy</a>
    </div>

    <section class="panel filter-panel" aria-label="Bộ lọc lịch dạy">
        <form method="GET" action="{{ route('admin.schedules.index') }}" class="filters">
            <div class="field">
                <label for="q">Học phần</label>
                <input id="q" name="q" type="search" placeholder="Tìm tên học phần" maxlength="255" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="field">
                <label for="filter-branch">Cơ sở</label>
                <select id="filter-branch" name="branch_id">
                    <option value="">Tất cả cơ sở</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="filter-teacher">Giảng viên</label>
                <select id="filter-teacher" name="teacher_id">
                    <option value="">Tất cả giảng viên</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(($filters['teacher_id'] ?? null) == $teacher->id)>{{ $teacher->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="filter-day">Ngày dạy</label>
                <select id="filter-day" name="day_of_week">
                    <option value="">Cả tuần</option>
                    @foreach ($days as $day => $label)
                        <option value="{{ $day }}" @selected(($filters['day_of_week'] ?? null) == $day)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="actions">
                <button class="button button-secondary" type="submit">Lọc lịch</button>
                @if ($filters)
                    <a class="button button-quiet" href="{{ route('admin.schedules.index') }}">Bỏ lọc</a>
                @endif
            </div>
        </form>
    </section>

    <section class="panel distribution-card" aria-labelledby="distribution-title">
        <div class="section-heading">
            <h2 id="distribution-title">Phân bổ tiết dạy theo ngày</h2>
            <p>{{ array_sum($distribution) }} tiết theo bộ lọc</p>
        </div>
        <ul class="distribution">
            @foreach ($distribution as $day => $periodCount)
                <li>
                    <span>{{ $days[$day] }}</span>
                    <span class="bar-track" aria-hidden="true"><span style="--filled: {{ $periodCount * 100 / max(1, max($distribution)) }}%"></span></span>
                    <strong>{{ $periodCount }} tiết</strong>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="panel schedule-panel" aria-labelledby="schedule-list">
        <div class="section-heading">
            <h2 id="schedule-list">Danh sách lịch dạy</h2>
            <p>{{ $schedules->total() }} lịch dạy</p>
        </div>
        @if ($schedules->isEmpty())
            <div class="empty">
                <p>Không tìm thấy lịch dạy.</p>
                @if ($filters)
                    <a class="button button-secondary" href="{{ route('admin.schedules.index') }}">Xem tất cả lịch</a>
                @endif
            </div>
        @else
            <div class="schedule-table">
                <table role="table" aria-labelledby="schedule-list">
                    <thead role="rowgroup">
                        <tr role="row">
                            <th role="columnheader" scope="col">Ngày và tiết</th>
                            <th role="columnheader" scope="col">Học phần</th>
                            <th role="columnheader" scope="col">Giảng viên</th>
                            <th role="columnheader" scope="col">Cơ sở và phòng</th>
                            <th role="columnheader" scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        @foreach ($schedules as $schedule)
                            <tr role="row">
                                <td role="cell">
                                    <strong>{{ $days[$schedule->day_of_week] ?? 'Chưa rõ' }}</strong>
                                    <span class="period">{{ \App\Support\ScheduleTime::label($schedule->shift_or_periods) }}</span>
                                </td>
                                <td role="cell"><strong>{{ $schedule->subject_name }}</strong></td>
                                <td role="cell">{{ $schedule->full_name }}</td>
                                <td role="cell">
                                    {{ $schedule->branch_name }}
                                    <small>Phòng {{ $schedule->room }}</small>
                                </td>
                                <td role="cell">
                                    <div class="actions row-actions">
                                        <a class="button button-secondary" href="{{ route('admin.schedules.edit', $schedule->id) }}" aria-label="Sửa lịch {{ $schedule->subject_name }} của {{ $schedule->full_name }}">Sửa</a>
                                        <a class="button button-quiet danger-text" href="{{ route('admin.schedules.confirm-destroy', $schedule->id) }}" aria-label="Xóa lịch {{ $schedule->subject_name }} của {{ $schedule->full_name }}">Xóa</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="paginator">
                <p>Hiển thị {{ $schedules->firstItem() }}–{{ $schedules->lastItem() }} / {{ $schedules->total() }}</p>
                @if ($schedules->hasPages())
                    <nav class="actions" aria-label="Phân trang">
                        @if ($schedules->previousPageUrl())
                            <a class="button button-secondary" rel="prev" href="{{ $schedules->previousPageUrl() }}">Trang trước</a>
                        @endif
                        @if ($schedules->hasMorePages())
                            <a class="button button-secondary" rel="next" href="{{ $schedules->nextPageUrl() }}">Trang sau</a>
                        @endif
                    </nav>
                @endif
            </div>
        @endif
    </section>
@endsection
