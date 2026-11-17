<?php

namespace App\Http\Controllers;

use App\Support\ScheduleTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
            ->orderByRaw("CASE WHEN shift_or_periods LIKE '__-%' THEN 1 ELSE 0 END")
            ->orderBy('schedules.shift_or_periods')->orderBy('schedules.id')
            ->get(['schedules.*', 'branches.branch_name', 'branches.address'])->groupBy('day_of_week');

        $days = [];
        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $lessons = $schedules->get($day->dayOfWeekIso, collect());
            if ($mode === 'week' || $lessons->isNotEmpty()) {
                $days[] = ['date' => $day, 'name' => ScheduleTime::DAYS[$day->dayOfWeekIso], 'lessons' => $lessons];
            }
        }

        return view('teacher.schedule', [
            'teacher' => $teacher,
            'days' => $days,
            'mode' => $mode,
            'date' => $date->toDateString(),
            'today' => $today->toDateString(),
            'previous' => ($mode === 'month' ? $start->subMonth() : $start->subWeek())->toDateString(),
            'next' => ($mode === 'month' ? $start->addMonth() : $start->addWeek())->toDateString(),
            'rangeLabel' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
        ]);
    }
}
