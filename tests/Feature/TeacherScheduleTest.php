<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_week_places_single_overlapping_and_adjacent_lessons_without_losing_older_records(): void
    {
        $teacher = $this->teacher();
        $otherTeacher = $this->teacher();
        $this->lesson($teacher, 'Lập trình PHP', 1, '1');
        $this->lesson($teacher, 'Phân tích dữ liệu', 1, '7-8');
        $this->lesson($teacher, 'Kiểm thử phần mềm', 1, '8-9');
        $this->lesson($teacher, 'Mạng máy tính', 1, '10-11');
        $this->lesson($teacher, 'Lịch cũ', 7, 'Ca chiều');
        $this->lesson($otherTeacher, 'Lịch của giảng viên khác', 1, '7-8');

        $this->actingAs($teacher)->get(route('teacher.schedule', ['view' => 'week', 'date' => '2026-11-18']))
            ->assertOk()
            ->assertSee('16/11/2026 – 22/11/2026')
            ->assertSee('datetime="2026-11-16"', false)
            ->assertSee('datetime="2026-11-22"', false)
            ->assertSee('Tiết 12')
            ->assertSee('--start: 1; --length: 1; --lane: 0; --lane-count: 1')
            ->assertSee('--start: 7; --length: 2; --lane: 0; --lane-count: 2')
            ->assertSee('--start: 8; --length: 2; --lane: 1; --lane-count: 2')
            ->assertSee('--start: 10; --length: 2; --lane: 0; --lane-count: 1')
            ->assertSee('Trùng tiết')
            ->assertSee('Buổi dạy chưa có số tiết chuẩn')
            ->assertSee('Ca chiều')
            ->assertDontSee('Lịch của giảng viên khác');
    }

    public function test_month_keeps_empty_days_and_supports_date_navigation(): void
    {
        $teacher = $this->teacher();
        $this->lesson($teacher, 'Thiết kế web', 1, '7-8');
        $month = route('teacher.schedule', ['view' => 'month', 'date' => '2026-11-18']);

        $this->actingAs($teacher)->get($month)
            ->assertOk()
            ->assertSee('01/11/2026 – 30/11/2026')
            ->assertSee('datetime="2026-11-01"', false)
            ->assertSee('datetime="2026-11-30"', false)
            ->assertSee('month-has-lessons')
            ->assertSee('5 buổi dạy')
            ->assertSee(route('teacher.schedule', ['view' => 'month', 'date' => '2026-10-01']))
            ->assertSee(route('teacher.schedule', ['view' => 'month', 'date' => '2026-12-01']))
            ->assertSee(route('teacher.schedule', ['view' => 'week', 'date' => '2026-11-18']));

        $this->get($month)->assertOk()->assertSee('01/11/2026 – 30/11/2026');
    }

    public function test_empty_week_has_a_grid_and_marks_the_current_date(): void
    {
        $teacher = $this->teacher();
        $today = CarbonImmutable::today('Asia/Ho_Chi_Minh');

        $this->actingAs($teacher)->get(route('teacher.schedule', ['view' => 'week', 'date' => $today->toDateString()]))
            ->assertOk()
            ->assertSee('Không có lịch dạy trong tuần này.')
            ->assertSee('Tiết 12')
            ->assertSee('datetime="'.$today->toDateString().'"', false)
            ->assertSee('aria-current="date"', false)
            ->assertDontSee('data-subject=', false);
    }

    private function teacher(): User
    {
        $user = User::factory()->create();
        $teacherId = DB::table('teachers')->insertGetId([
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => '0000000001',
            'expertise' => 'Công nghệ phần mềm',
        ]);
        $user->forceFill(['teacher_id' => $teacherId, 'password_state' => User::PASSWORD_ACTIVE])->save();

        return $user;
    }

    private function lesson(User $teacher, string $subject, int $weekday, string $periods): void
    {
        $branchId = DB::table('branches')->where('branch_name', 'Cơ sở Tân Phú')->value('id')
            ?? DB::table('branches')->insertGetId(['branch_name' => 'Cơ sở Tân Phú', 'address' => 'Địa chỉ cơ sở']);

        DB::table('schedules')->insert([
            'teacher_id' => $teacher->teacher_id,
            'branch_id' => $branchId,
            'subject_name' => $subject,
            'room' => 'P01',
            'day_of_week' => $weekday,
            'shift_or_periods' => $periods,
        ]);
    }
}
