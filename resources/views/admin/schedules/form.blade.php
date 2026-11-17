@extends('layouts.schedule')

@php
    $title = $schedule === null ? 'Thêm lịch dạy' : 'Sửa lịch dạy';
    $periods = \App\Support\ScheduleTime::parse($schedule->shift_or_periods ?? '') ?? ['', ''];
@endphp

@section('title', $title)

@section('content')
    <script defer src="{{ asset('js/schedule-suggestions.js') }}"></script>
    <div class="page-heading narrow">
        <div>
            <a class="back-link" href="{{ route('admin.schedules.index') }}">Về danh sách lịch dạy</a>
            <h1>{{ $title }}</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="notice notice-error narrow" role="alert">
            <strong>Chưa thể lưu lịch dạy.</strong>
            <ul>
                @foreach ($errors->keys() as $field)
                    <li><a href="#{{ $field }}">{{ $errors->first($field) }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <form class="panel fields narrow edit-form" method="POST" action="{{ $schedule ? route('admin.schedules.update', $schedule->id) : route('admin.schedules.store') }}">
        @csrf
        @if ($schedule)
            @method('PUT')
        @endif
        <div class="field">
            <label for="subject_name">Tên học phần</label>
            <input id="subject_name" name="subject_name" value="{{ old('subject_name', $schedule->subject_name ?? '') }}" maxlength="255" required @error('subject_name') aria-invalid="true" aria-describedby="subject_name-error" @enderror>
            @error('subject_name')<p class="field-error" id="subject_name-error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="teacher_id">Giảng viên</label>
            <select id="teacher_id" name="teacher_id" required @error('teacher_id') aria-invalid="true" aria-describedby="teacher_id-error" @enderror>
                <option value="">Chọn giảng viên</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('teacher_id', $schedule->teacher_id ?? '') == $teacher->id)>{{ $teacher->full_name }} · {{ $teacher->expertise }}</option>
                @endforeach
            </select>
            @error('teacher_id')<p class="field-error" id="teacher_id-error">{{ $message }}</p>@enderror
        </div>

        <div class="field-grid">
            <div class="field">
                <label for="branch_id">Cơ sở</label>
                <select id="branch_id" name="branch_id" required @error('branch_id') aria-invalid="true" aria-describedby="branch_id-error" @enderror>
                    <option value="">Chọn cơ sở</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $schedule->branch_id ?? '') == $branch->id)>{{ $branch->branch_name }}</option>
                    @endforeach
                </select>
                @error('branch_id')<p class="field-error" id="branch_id-error">{{ $message }}</p>@enderror
            </div>
            <div class="field">
                <label for="room">Phòng học</label>
                <input id="room" name="room" value="{{ old('room', $schedule->room ?? '') }}" maxlength="50" placeholder="Ví dụ P01" required @error('room') aria-invalid="true" aria-describedby="room-error" @enderror>
                @error('room')<p class="field-error" id="room-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="field-grid field-grid-time">
            <div class="field">
                <label for="day_of_week">Ngày dạy</label>
                <select id="day_of_week" name="day_of_week" required @error('day_of_week') aria-invalid="true" aria-describedby="day_of_week-error" @enderror>
                    <option value="">Chọn ngày</option>
                    @foreach ($days as $day => $label)
                        <option value="{{ $day }}" @selected(old('day_of_week', $schedule->day_of_week ?? '') == $day)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('day_of_week')<p class="field-error" id="day_of_week-error">{{ $message }}</p>@enderror
            </div>
            @foreach (['start_period' => 'Tiết bắt đầu', 'end_period' => 'Tiết kết thúc'] as $field => $label)
                <div class="field">
                    <label for="{{ $field }}">{{ $label }}</label>
                    <select id="{{ $field }}" name="{{ $field }}" required @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        <option value="">Chọn tiết</option>
                        @foreach (range(1, \App\Support\ScheduleTime::LAST_PERIOD) as $period)
                            <option value="{{ $period }}" @selected(old($field, $field === 'start_period' ? $periods[0] : ($periods[1] ?? $periods[0])) == $period)>Tiết {{ $period }}</option>
                        @endforeach
                    </select>
                    @error($field)<p class="field-error" id="{{ $field }}-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
        <p class="muted field-hint">Nếu buổi học chỉ có một tiết, chọn cùng số cho cả hai ô.</p>
        <section class="suggestion-panel" aria-labelledby="suggestion-title"
                 data-suggestions="{{ route('admin.schedules.suggestions', [], false) }}"
                 data-schedule="{{ $schedule->id ?? '' }}" hidden>
            <h2 id="suggestion-title">Gợi ý xếp lịch</h2>
            <p class="muted field-hint">Chọn chuyên môn, cơ sở, phòng và khoảng tiết để tìm giảng viên rảnh hoặc tiết khác trong cùng ngày.</p>
            <div class="suggestion-controls">
                <div class="field">
                    <label for="expertise">Chuyên môn cần tìm</label>
                    <select id="expertise">
                        <option value="">Chọn chuyên môn</option>
                        @foreach ($teachers->pluck('expertise')->unique()->sort() as $expertise)
                            <option value="{{ $expertise }}">{{ $expertise }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="button button-secondary" data-find-suggestions type="button">Tìm gợi ý</button>
            </div>
            <p class="hint-status" data-suggestion-status role="status" aria-atomic="true"></p>
            <div data-suggestion-results></div>
        </section>
        <div class="actions form-actions">
            <button class="button button-primary" type="submit">Lưu lịch dạy</button>
            <a class="button button-secondary" href="{{ route('admin.schedules.index') }}">Hủy</a>
        </div>
    </form>
@endsection
