<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveScheduleRequest;
use App\Services\ScheduleSuggestions;
use App\Support\ScheduleTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = DB::table('schedules')
            ->join('teachers', 'teachers.id', '=', 'schedules.teacher_id')
            ->join('branches', 'branches.id', '=', 'schedules.branch_id')
            ->select('schedules.*', 'teachers.full_name', 'branches.branch_name');

        foreach (['branch_id', 'teacher_id', 'day_of_week'] as $filter) {
            if (isset($filters[$filter])) {
                $query->where("schedules.$filter", $filters[$filter]);
            }
        }

        if (isset($filters['q'])) {
            $query->whereLike('schedules.subject_name', '%'.$filters['q'].'%');
        }

        $distribution = array_fill_keys(array_keys(ScheduleTime::DAYS), 0);
        foreach ((clone $query)->select('schedules.day_of_week', 'schedules.shift_or_periods')->cursor() as $lesson) {
            $periods = ScheduleTime::parse($lesson->shift_or_periods);
            if ($periods !== null && isset($distribution[$lesson->day_of_week])) {
                $distribution[$lesson->day_of_week] += $periods[1] - $periods[0] + 1;
            }
        }

        $schedules = $query->orderBy('schedules.day_of_week')
            ->orderByRaw("CASE WHEN shift_or_periods LIKE '__-%' THEN 1 ELSE 0 END")
            ->orderBy('schedules.shift_or_periods')
            ->orderBy('schedules.id')
            ->paginate(12)->withQueryString();

        return view('admin.schedules.index', $this->formOptions() + [
            'schedules' => $schedules,
            'distribution' => $distribution,
            'filters' => array_filter($filters, fn ($value): bool => $value !== null),
        ]);
    }

    public function create(): View
    {
        return view('admin.schedules.form', $this->formOptions() + ['schedule' => null]);
    }

    public function suggestions(Request $request, ScheduleSuggestions $suggestions): JsonResponse
    {
        $data = $request->validate([
            'expertise' => ['required', 'string', 'max:255', 'exists:teachers,expertise'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'room' => ['required', 'string', 'max:50'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_period' => ['required', 'integer', 'between:1,'.ScheduleTime::LAST_PERIOD],
            'end_period' => ['required', 'integer', 'between:1,'.ScheduleTime::LAST_PERIOD, 'gte:start_period'],
            'exclude' => ['nullable', 'integer', 'exists:schedules,id'],
        ]);

        return response()->json($suggestions->forSlot(
            $data['expertise'], (int) $data['branch_id'], $data['room'], (int) $data['day_of_week'],
            (int) $data['start_period'], (int) $data['end_period'],
            isset($data['exclude']) ? (int) $data['exclude'] : null,
        ))->header('Cache-Control', 'no-store');
    }

    public function store(SaveScheduleRequest $request): RedirectResponse
    {
        $this->save($request);

        return to_route('admin.schedules.index')->with('status', 'Đã thêm lịch dạy.');
    }

    public function edit(int $schedule): View
    {
        return view('admin.schedules.form', $this->formOptions() + [
            'schedule' => DB::table('schedules')->where('id', $schedule)->firstOrFail(),
        ]);
    }

    public function update(SaveScheduleRequest $request, int $schedule): RedirectResponse
    {
        $this->save($request, $schedule);

        return to_route('admin.schedules.index')->with('status', 'Đã cập nhật lịch dạy.');
    }

    public function confirmDestroy(int $schedule): View
    {
        $record = DB::table('schedules')
            ->join('teachers', 'teachers.id', '=', 'schedules.teacher_id')
            ->join('branches', 'branches.id', '=', 'schedules.branch_id')
            ->select('schedules.*', 'teachers.full_name', 'branches.branch_name')
            ->where('schedules.id', $schedule)
            ->firstOrFail();

        return view('admin.schedules.delete', ['schedule' => $record, 'days' => ScheduleTime::DAYS]);
    }

    public function destroy(int $schedule): RedirectResponse
    {
        abort_unless(DB::table('schedules')->where('id', $schedule)->delete() === 1, 404);

        return to_route('admin.schedules.index')->with('status', 'Đã xóa lịch dạy.');
    }

    private function formOptions(): array
    {
        return [
            'days' => ScheduleTime::DAYS,
            'branches' => DB::table('branches')->orderBy('branch_name')->get(['id', 'branch_name']),
            'teachers' => DB::table('teachers')->orderBy('full_name')->get(['id', 'full_name', 'expertise']),
        ];
    }

    private function save(SaveScheduleRequest $request, ?int $scheduleId = null): void
    {
        $data = $request->validated();
        $teacherId = (int) $data['teacher_id'];
        $branchId = (int) $data['branch_id'];
        $room = ScheduleTime::room($data['room']);
        $start = (int) $data['start_period'];
        $end = (int) $data['end_period'];

        DB::transaction(function () use ($data, $scheduleId, $teacherId, $branchId, $room, $start, $end): void {
            // Lock the branch and teacher before checking availability so concurrent saves share the same locks.
            DB::table('branches')->where('id', $branchId)->lockForUpdate()->firstOrFail();
            DB::table('teachers')->where('id', $teacherId)->lockForUpdate()->firstOrFail();

            $target = DB::table('schedules')->where('id', $scheduleId);
            if ($scheduleId !== null) {
                (clone $target)->lockForUpdate()->firstOrFail();
            }

            $existing = DB::table('schedules')->where('day_of_week', $data['day_of_week'])
                ->where(function ($query) use ($teacherId, $branchId): void {
                    $query->where('teacher_id', $teacherId)->orWhere('branch_id', $branchId);
                })
                ->when($scheduleId !== null, fn ($query) => $query->where('id', '<>', $scheduleId))
                ->lockForUpdate()->get(['teacher_id', 'branch_id', 'room', 'subject_name', 'shift_or_periods']);

            $errors = [];
            foreach ($existing as $record) {
                $sameTeacher = (int) $record->teacher_id === $teacherId;
                $sameRoom = (int) $record->branch_id === $branchId && ScheduleTime::room($record->room) === $room;

                if (! $sameTeacher && ! $sameRoom) {
                    continue;
                }

                $periods = ScheduleTime::parse($record->shift_or_periods);
                if ($periods === null) {
                    throw ValidationException::withMessages(['start_period' => "Cần chuẩn hóa tiết của lịch «{$record->subject_name}» trước khi phân lịch này."]);
                }

                if (! ScheduleTime::overlaps($periods, $start, $end)) {
                    continue;
                }

                if ($sameTeacher) {
                    $errors['teacher_id'] ??= "Giảng viên đã dạy «{$record->subject_name}» vào tiết {$record->shift_or_periods} cùng ngày.";
                }
                if ($sameRoom) {
                    $errors['room'] ??= "Phòng $room đã có «{$record->subject_name}» vào tiết {$record->shift_or_periods} cùng ngày.";
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $values = [
                'subject_name' => $data['subject_name'],
                'teacher_id' => $teacherId,
                'branch_id' => $branchId,
                'room' => $room,
                'day_of_week' => $data['day_of_week'],
                'shift_or_periods' => "$start-$end",
                'updated_at' => now(),
            ];

            if ($scheduleId === null) {
                DB::table('schedules')->insert($values + ['created_at' => now()]);
            } else {
                $target->update($values);
            }
        }, 3);
    }
}
