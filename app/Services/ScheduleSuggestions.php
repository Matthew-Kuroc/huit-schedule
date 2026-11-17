<?php

namespace App\Services;

use App\Support\ScheduleTime;
use Illuminate\Support\Facades\DB;

class ScheduleSuggestions
{
    public function forSlot(string $expertise, int $branchId, string $room, int $day, int $start, int $end, ?int $exclude): array
    {
        $teachers = DB::table('teachers')->where('expertise', $expertise)->orderBy('full_name')->get(['id', 'full_name']);
        $bookings = DB::table('schedules')->where('day_of_week', $day)
            ->where(function ($query) use ($teachers, $branchId): void {
                $query->whereIn('teacher_id', $teachers->pluck('id'))->orWhere('branch_id', $branchId);
            })
            ->when($exclude !== null, fn ($query) => $query->where('id', '<>', $exclude))
            ->get(['teacher_id', 'branch_id', 'room', 'shift_or_periods']);

        $room = ScheduleTime::room($room);
        $roomBookings = $bookings->filter(fn ($booking): bool => (int) $booking->branch_id === $branchId && ScheduleTime::room($booking->room) === $room)
            ->map(fn ($booking): ?array => ScheduleTime::parse($booking->shift_or_periods))->all();
        $teacherBookings = $bookings->groupBy('teacher_id');
        $exact = [];
        $alternatives = [];
        $length = $end - $start + 1;

        foreach ($teachers as $teacher) {
            $lessons = [];
            foreach ($teacherBookings->get($teacher->id, []) as $booking) {
                $periods = ScheduleTime::parse($booking->shift_or_periods);
                if ($periods === null) {
                    continue 2;
                }
                $lessons[] = [$periods[0], $periods[1], (int) $booking->branch_id];
            }

            $teacherAlternatives = [];
            for ($candidate = 1; $candidate + $length - 1 <= ScheduleTime::LAST_PERIOD; $candidate++) {
                $finish = $candidate + $length - 1;
                if ($this->roomIsBusy($roomBookings, $candidate, $finish)) {
                    continue;
                }

                $fit = $this->teacherFit($lessons, $branchId, $candidate, $finish);
                if ($fit === null) {
                    continue;
                }

                $option = [
                    'teacher_id' => (int) $teacher->id,
                    'full_name' => $teacher->full_name,
                    'start_period' => $candidate,
                    'end_period' => $finish,
                    'period_label' => ScheduleTime::label("$candidate-$finish"),
                    'reason' => $fit['reason'],
                    'score' => [...$fit['score'], abs($candidate - $start), $teacher->full_name, $candidate],
                ];
                if ($candidate === $start) {
                    $exact[] = $option;
                } else {
                    $teacherAlternatives[] = $option;
                }
            }

            if ($teacherAlternatives !== []) {
                usort($teacherAlternatives, fn (array $a, array $b): int => $a['score'] <=> $b['score']);
                $alternatives[] = $teacherAlternatives[0];
            }
        }

        return [
            'room_busy' => $this->roomIsBusy($roomBookings, $start, $end),
            'exact' => $this->rank($exact, 12),
            'alternatives' => $this->rank($alternatives, 6),
        ];
    }

    private function roomIsBusy(array $roomBookings, int $start, int $end): bool
    {
        foreach ($roomBookings as $periods) {
            if ($periods === null || ScheduleTime::overlaps($periods, $start, $end)) {
                return true;
            }
        }

        return false;
    }

    private function teacherFit(array $lessons, int $branchId, int $start, int $end): ?array
    {
        $previous = null;
        $next = null;
        foreach ($lessons as [$first, $last, $branch]) {
            if (ScheduleTime::overlaps([$first, $last], $start, $end)) {
                return null;
            }

            if ($last < $start && ($previous === null || $last > $previous[0])) {
                $previous = [$last, $branch];
            } elseif ($first > $end && ($next === null || $first < $next[0])) {
                $next = [$first, $branch];
            }
        }

        $neighbors = [];
        if ($previous !== null) {
            $neighbors[] = [$start - $previous[0] - 1, $previous[1]];
        }
        if ($next !== null) {
            $neighbors[] = [$next[0] - $end - 1, $next[1]];
        }

        $relocations = 0;
        $waiting = 0;
        foreach ($neighbors as [$gap, $branch]) {
            if ($branch !== $branchId) {
                if ($gap === 0) {
                    return null;
                }
                $relocations++;
            }
            $waiting += $gap;
        }

        $reason = match (true) {
            $neighbors === [] => 'Chưa có lớp khác trong ngày.',
            $relocations === 0 && $waiting === 0 => 'Sát tiết dạy khác tại cùng cơ sở.',
            $relocations === 0 => "Cùng cơ sở với lớp gần nhất · $waiting tiết chờ.",
            default => "Có $relocations lần đổi cơ sở · $waiting tiết trống giữa các lớp.",
        };

        return ['score' => [$relocations, $neighbors === [] ? 1 : 0, $waiting], 'reason' => $reason];
    }

    private function rank(array $options, int $limit): array
    {
        usort($options, fn (array $a, array $b): int => $a['score'] <=> $b['score']);

        return array_map(function (array $option): array {
            unset($option['score']);

            return $option;
        }, array_slice($options, 0, $limit));
    }
}
