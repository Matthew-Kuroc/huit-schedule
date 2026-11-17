<?php

namespace App\Support;

use Illuminate\Support\Str;

final class ScheduleTime
{
    public const DAYS = [1 => 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ nhật'];

    public const LAST_PERIOD = 12;

    /** @return array{int, int}|null */
    public static function parse(string $value): ?array
    {
        if (! preg_match('/^(\d{1,2})(?:-(\d{1,2}))?$/D', $value, $matches)) {
            return null;
        }

        $start = (int) $matches[1];
        $end = (int) ($matches[2] ?? $start);

        return $start >= 1 && $end >= $start && $end <= self::LAST_PERIOD ? [$start, $end] : null;
    }

    /** @param array{int, int} $periods */
    public static function overlaps(array $periods, int $start, int $end): bool
    {
        return $periods[0] <= $end && $periods[1] >= $start;
    }

    public static function label(string $value): string
    {
        $periods = self::parse($value);
        if ($periods === null) {
            return $value;
        }

        return $periods[0] === $periods[1] ? "Tiết {$periods[0]}" : "Tiết {$periods[0]}–{$periods[1]}";
    }

    public static function room(string $value): string
    {
        return Str::upper(Str::squish($value));
    }
}
