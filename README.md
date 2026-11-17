# HUIT Schedule Hub

Ứng dụng quản lý lịch giảng dạy đa cơ sở cho bài thực hành Lập trình mã nguồn mở. Dự án dùng Laravel 13, Blade, MySQL, Composer, phpdotenv và Faker.

## Chức năng

- Quản trị viên xem, lọc, thêm, sửa và xóa lịch dạy. Máy chủ chặn lịch chồng tiết của giảng viên hoặc của cùng một phòng tại cùng cơ sở.
- Form xếp lịch gợi ý giảng viên đúng chuyên môn, kiểm tra phòng và tìm các khung tiết khác cùng ngày. Hệ thống ưu tiên ít đổi cơ sở rồi ít tiết chờ. Hai lớp liền tiết ở hai cơ sở khác nhau không được gợi ý. Đây là quy tắc theo tiết, không tính thời gian di chuyển thực tế.
- Giảng viên xem lịch cá nhân theo lưới tuần hoặc tháng, bấm vào từng buổi để xem phòng, cơ sở và địa chỉ. Lịch lặp theo thứ trong tuần.
- Biểu đồ của quản trị viên cộng số tiết dạy theo ngày trên toàn bộ kết quả lọc, gồm cả các lịch ở những trang sau.

## Cài trên Laragon

Cần PHP 8.4, Composer và MySQL. Mở MySQL trong Laragon, sau đó tạo database rỗng `huit_db` trong phpMyAdmin với collation `utf8mb4_unicode_ci`.

Mở PowerShell tại thư mục chứa `artisan`:

```powershell
composer install
Copy-Item .env.example .env
```

Chỉ sao chép `.env.example` khi cài mới. Trong `.env`, điền thông số MySQL trên máy của bạn:

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=huit_db
DB_USERNAME=root
DB_PASSWORD=
```

Nếu MySQL yêu cầu mật khẩu, điền giá trị thực vào `DB_PASSWORD`. Laravel đọc `DB_DATABASE` làm tên database.

Sau khi lưu `.env`, tạo khóa cho lần cài mới và chạy migration:

```powershell
php artisan key:generate
php artisan migrate
```

### Nạp dữ liệu mẫu

Sau khi chạy migration, chọn một cách cho database mới:

- **phpMyAdmin:** chọn database `huit_db` → **Import** → chọn `database/huit_demo.sql` → **Import**. File SQL chứa dữ liệu ba bảng nghiệp vụ và được dùng khi ba bảng đang trống.
- **Artisan:** chạy `php artisan db:seed` để tạo cùng bộ dữ liệu bằng Laravel và Faker.

Bộ mẫu có 8 cơ sở, 48 giảng viên và 144 lịch dạy. Chuẩn bị tài khoản quản trị và tạo tài khoản cho tất cả giảng viên:

```powershell
php artisan schedule:create-admin admin@example.test --generate-password
php artisan schedule:provision-teachers
```

Terminal hiển thị mật khẩu tạm của admin. Sau khi đăng nhập và đổi mật khẩu, mở **Giảng viên** trong thanh điều hướng để đặt mật khẩu tạm cho từng tài khoản. Lệnh cấp hàng loạt giữ nguyên các tài khoản đang có. Tài khoản mới ở trạng thái **Chờ cấp mật khẩu** cho đến khi admin đặt mật khẩu tạm. Giảng viên sử dụng email trên hồ sơ và tự đặt mật khẩu mới sau lần đăng nhập đầu tiên.

### Mở website

```powershell
php artisan serve
```

Mở `http://127.0.0.1:8000`. Apache của Laragon cũng có thể phục vụ dự án ở `http://huit-schedule.test`:

1. Sao chép file mẫu `config/laragon/huit-schedule.conf` thành `C:\laragon\etc\apache2\sites-enabled\huit-schedule.test.conf`. Nếu đã có `auto.huit-schedule.test.conf`, đổi tên file tự sinh thành `huit-schedule.test.conf` rồi thay nội dung bằng file mẫu để chỉ có một Virtual Host cho tên miền này.
2. Sửa đường dẫn dự án và cổng Apache trong file mẫu cho khớp máy. `DocumentRoot` phải trỏ tới thư mục `public`. Bản mẫu thêm cổng 80 trên loopback cho máy dùng Apache cổng 8080. Nếu Apache đã lắng nghe cổng 80, bỏ dòng `Listen` trong file mẫu và dùng cổng đang có trong thẻ `VirtualHost`.
3. Thêm dòng `127.0.0.1 huit-schedule.test` vào `C:\Windows\System32\drivers\etc\hosts` nếu Laragon chưa tạo. Bấm **Reload** ở hàng Apache trong Laragon rồi mở tên miền.
4. Đặt `APP_URL` trong `.env` thành địa chỉ bạn dùng để chạy ứng dụng.

Giao diện sử dụng CSS và JavaScript trong `public/`, nên cài Composer là đủ để chạy bản này.

### Demo mật khẩu với Mailpit

Bật Mailpit trong Laragon. File `.env.example` cấu hình SMTP `127.0.0.1:1025`. Mở hộp thư Mailpit tại `http://127.0.0.1:8025`.

- **Đổi mật khẩu** có trong thanh điều hướng sau khi đăng nhập. Nhập mật khẩu hiện tại để xác minh, sau đó nhập mật khẩu mới. Mailpit nhận thư thông báo sau khi lưu.
- **Quên mật khẩu?** có ở trang đăng nhập. Nhập email của tài khoản đã được cấp mật khẩu rồi mở thư **Đặt lại mật khẩu HUIT Schedule Hub** trong Mailpit. Liên kết dùng một lần và hết hạn sau 60 phút.

## Kịch bản demo

1. Đăng nhập admin và lọc lịch theo giảng viên hoặc cơ sở. Biểu đồ đổi theo bộ lọc.
2. Thêm lịch vào thứ Hai, tiết 1 ở cơ sở Tân Phú. Chọn phòng **P99** và chuyên môn **Công nghệ phần mềm** để xem những giảng viên rảnh. Đổi sang **P01** để xem cảnh báo phòng đã có lớp và gợi ý khung tiết khác.
3. Thử lưu lịch cho **Dương Tiến Đạt** vào thứ Hai, tiết 1 để minh họa kiểm tra trùng giảng viên tại máy chủ.
4. Đăng nhập bằng tài khoản giảng viên và chuyển giữa **Theo tuần** và **Theo tháng**.

## Kiểm tra cú pháp và giấy phép

GitHub Actions chạy `php -l` trên các file PHP trong Git mỗi lần push và khi có pull request. Xem kết quả ở tab **Actions**.

Dự án dùng giấy phép [MIT](LICENSE).
