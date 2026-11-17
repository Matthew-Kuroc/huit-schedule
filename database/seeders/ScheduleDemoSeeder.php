<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScheduleDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            if (DB::table('branches')->exists() || DB::table('teachers')->exists() || DB::table('schedules')->exists()) {
                $this->command?->warn('Bỏ qua dữ liệu demo vì các bảng nghiệp vụ đã có dữ liệu.');

                return;
            }

            $timestamp = now();
            $branchIds = [];

            foreach ([
                ['Cơ sở Tân Phú', 'Quận Tân Phú, TP. Hồ Chí Minh'],
                ['Cơ sở Tân Bình', 'Quận Tân Bình, TP. Hồ Chí Minh'],
                ['Cơ sở Bình Tân', 'Quận Bình Tân, TP. Hồ Chí Minh'],
                ['Trung tâm thực hành Quận 6', 'Quận 6, TP. Hồ Chí Minh'],
                ['Trung tâm ngoại ngữ Quận 10', 'Quận 10, TP. Hồ Chí Minh'],
                ['Phòng máy Gò Vấp', 'Quận Gò Vấp, TP. Hồ Chí Minh'],
                ['Trung tâm liên kết Bình Thạnh', 'Quận Bình Thạnh, TP. Hồ Chí Minh'],
                ['Trung tâm thực nghiệm Thủ Đức', 'Khu vực Thủ Đức, TP. Hồ Chí Minh'],
            ] as [$name, $address]) {
                $branchIds[] = DB::table('branches')->insertGetId([
                    'branch_name' => $name,
                    'address' => $address,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            $coursesByExpertise = [
                'Công nghệ phần mềm' => ['Lập trình PHP', 'Phát triển ứng dụng web', 'Kiểm thử phần mềm'],
                'Khoa học dữ liệu' => ['Phân tích dữ liệu', 'Học máy cơ bản', 'Trực quan hóa dữ liệu'],
                'Mạng máy tính' => ['Mạng máy tính', 'Quản trị hệ thống', 'An toàn thông tin'],
                'Toán ứng dụng' => ['Đại số tuyến tính', 'Xác suất thống kê', 'Toán rời rạc'],
                'Công nghệ thực phẩm' => ['Hóa học thực phẩm', 'Công nghệ chế biến', 'Quản lý an toàn thực phẩm'],
                'Công nghệ sinh học' => ['Vi sinh vật học', 'Sinh học phân tử', 'Kỹ thuật phòng thí nghiệm'],
                'Kế toán' => ['Nguyên lý kế toán', 'Kế toán tài chính', 'Kế toán quản trị'],
                'Quản trị kinh doanh' => ['Quản trị học', 'Marketing căn bản', 'Khởi sự kinh doanh'],
                'Ngôn ngữ Anh' => ['Tiếng Anh giao tiếp', 'Tiếng Anh học thuật', 'Biên dịch cơ bản'],
                'Điện và tự động hóa' => ['Mạch điện', 'Điều khiển tự động', 'Vi điều khiển'],
                'Kỹ thuật môi trường' => ['Hóa môi trường', 'Quản lý chất thải', 'Xử lý nước'],
                'Logistics' => ['Quản lý kho', 'Vận tải đa phương thức', 'Quản trị chuỗi cung ứng'],
            ];

            $teacherProfiles = self::teacherProfiles();
            $teacherIndex = 0;
            $roomNumbersBySlot = [];
            $schedules = [];

            foreach ($coursesByExpertise as $expertise => $courses) {
                for ($teacherInGroup = 0; $teacherInGroup < 4; $teacherInGroup++) {
                    $teacherNumber = $teacherIndex + 1;
                    $teacherId = DB::table('teachers')->insertGetId($teacherProfiles[$teacherIndex] + [
                        'phone' => sprintf('000000%04d', $teacherNumber),
                        'expertise' => $expertise,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                    foreach ($courses as $lessonIndex => $subject) {
                        $branchIndex = (intdiv($teacherIndex, 4) + $teacherInGroup + 3 * $lessonIndex) % count($branchIds);
                        $branchId = $branchIds[$branchIndex];
                        $sameDayAsFirstLesson = $teacherInGroup === 0 && $lessonIndex === 1;

                        // ISO weekday numbering (Monday = 1); each block holds up to three periods.
                        $day = ($teacherIndex + ($sameDayAsFirstLesson ? 0 : 2 * $lessonIndex)) % 6 + 1;
                        $block = ($teacherIndex + intdiv($teacherIndex, 6) + ($sameDayAsFirstLesson ? 2 : $lessonIndex)) % 4;
                        $firstPeriod = $block * 3 + 1;
                        $lastPeriod = $firstPeriod + ($teacherIndex + $lessonIndex) % 3;

                        // Assign distinct rooms within each branch, day and period block.
                        $roomSlot = "$branchId:$day:$block";
                        $roomNumber = ($roomNumbersBySlot[$roomSlot] ?? 0) + 1;
                        $roomNumbersBySlot[$roomSlot] = $roomNumber;

                        $schedules[] = [
                            'teacher_id' => $teacherId,
                            'branch_id' => $branchId,
                            'subject_name' => $subject,
                            'room' => sprintf('P%02d', $roomNumber),
                            'day_of_week' => $day,
                            'shift_or_periods' => "$firstPeriod-$lastPeriod",
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }

                    $teacherIndex++;
                }
            }

            DB::table('schedules')->insert($schedules);
        });
    }

    /**
     * @return list<array{full_name: string, email: string}>
     */
    public static function teacherProfiles(): array
    {
        $faker = Factory::create('vi_VN');
        $faker->seed(20261118);
        $names = $faker->shuffleArray([
            'Nguyễn Minh Anh', 'Trần Thị Thu Hà', 'Lê Hoàng Nam', 'Phạm Ngọc Linh',
            'Hoàng Gia Huy', 'Huỳnh Thanh Trúc', 'Phan Quốc Bảo', 'Vũ Thảo Nguyên',
            'Võ Minh Tâm', 'Đặng Phương Thảo', 'Bùi Tuấn Kiệt', 'Đỗ Thị Thanh Hương',
            'Hồ Đức Anh', 'Ngô Kim Ngân', 'Dương Quang Huy', 'Lý Khánh Vy',
            'Nguyễn Đức Thành', 'Trần Ngọc Mai', 'Lê Trung Hiếu', 'Phạm Bảo Châu',
            'Hoàng Anh Tuấn', 'Huỳnh Thị Bích Ngọc', 'Phan Nhật Minh', 'Vũ Thanh Vân',
            'Võ Quốc Hưng', 'Đặng Hoài An', 'Bùi Minh Khôi', 'Đỗ Gia Linh',
            'Hồ Thanh Phong', 'Ngô Thu Trang', 'Dương Tiến Đạt', 'Lý Thị Hồng Nhung',
            'Nguyễn Thị Mỹ Duyên', 'Trần Anh Khoa', 'Lê Thị Quỳnh Anh', 'Phạm Văn Bình',
            'Hoàng Ngọc Ánh', 'Huỳnh Văn Toàn', 'Phan Thùy Dương', 'Vũ Đức Long',
            'Võ Thị Lan Anh', 'Đặng Yến Nhi', 'Bùi Trọng Nghĩa', 'Đỗ Minh Quân',
            'Hồ Thu Phương', 'Ngô Quang Vinh', 'Dương Lan Hương', 'Lý Hải Đăng',
        ]);

        return array_map(static fn (string $name): array => [
            'full_name' => $name,
            'email' => Str::slug($name, '', 'vi').'@gmail.com',
        ], $names);
    }
}
