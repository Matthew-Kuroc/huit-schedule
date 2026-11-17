<?php

namespace App\Http\Controllers;

use App\Support\ScheduleTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use stdClass;

class TeacherScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'view' => ['nullable', 'in:week,month'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $mode = $filters['view'] ?? 'week';
        $today = CarbonImmutable::today('Asia/Ho_Chi_Minh');
        $date = isset($filters['date']) ? CarbonImmutable::parse($filters['date'], 'Asia/Ho_Chi_Minh') : $today;
        $start = $mode === 'month' ? $date->startOfMonth() : $date->startOfWeek(CarbonImmutable::MONDAY);
        $end = $mode === 'month' ? $date->endOfMonth() : $start->addDays(6)->endOfDay();
        $teacherId = $request->user()->teacher_id;
        $teacher = DB::table('teachers')->where('id', $teacherId)->firstOrFail();
        $schedules = DB::table('schedules')
            ->join('branches', 'branches.id', '=', 'schedules.branch_id')
            ->where('schedules.teacher_id', $teacherId)
            ->get(['schedules.*', 'branches.branch_name', 'branches.address'])
            ->map(function (stdClass $lesson): stdClass {
                $lesson->periods = ScheduleTime::parse($lesson->shift_or_periods);
                $lesson->periodLabel = ScheduleTime::label($lesson->shift_or_periods);
                $lesson->color = crc32($lesson->subject_name) % 5;

                return $lesson;
            })
            ->sortBy(fn (stdClass $lesson): array => [$lesson->periods[0] ?? PHP_INT_MAX, $lesson->id])
            ->groupBy('day_of_week');

        if ($mode === 'week') {
            $schedules->each($this->placeLessons(...));
        }

        $days = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $days[] = [
                'date' => $day,
                'name' => ScheduleTime::DAYS[$day->dayOfWeekIso],
                'isToday' => $day->isSameDay($today),
                'lessons' => $schedules->get($day->dayOfWeekIso, collect()),
            ];
        }

        return view('teacher.schedule', [
            'teacher' => $teacher,
            'days' => $days,
            'weekdays' => ScheduleTime::DAYS,
            'periods' => range(1, ScheduleTime::LAST_PERIOD),
            'monthOffset' => $start->dayOfWeekIso - 1,
            'monthTrailingDays' => 7 - $end->dayOfWeekIso,
            'lessonCount' => array_sum(array_map(fn (array $day): int => $day['lessons']->count(), $days)),
            'mode' => $mode,
            'date' => $date->toDateString(),
            'today' => $today->toDateString(),
            'previous' => ($mode === 'month' ? $start->subMonth() : $start->subWeek())->toDateString(),
            'next' => ($mode === 'month' ? $start->addMonth() : $start->addWeek())->toDateString(),
            'rangeLabel' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
        ]);
    }

    /** @param Collection<int, stdClass> $lessons */
    private function placeLessons(Collection $lessons): void
    {
        $groups = [];
        $groupEnd = 0;
        $laneEnds = [];

        foreach ($lessons->whereNotNull('periods') as $lesson) {
            [$start, $end] = $lesson->periods;

            if ($start > $groupEnd) {
                $groups[] = [];
                $laneEnds = [];
            }

            $lane = 0;
            while (($laneEnds[$lane] ?? 0) >= $start) {
                $lane++;
            }

            $lesson->lane = $lane;
            $laneEnds[$lane] = $end;
            $groupEnd = max($groupEnd, $end);
            $groups[array_key_last($groups)][] = $lesson;
        }

        foreach ($groups as $group) {
            $laneCount = 1 + max(array_column($group, 'lane'));
            foreach ($group as $lesson) {
                $lesson->laneCount = $laneCount;
            }
        }
    }
}
