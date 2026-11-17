<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TeacherAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAccountController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = $filters['q'] ?? '';
        $query = DB::table('teachers')->leftJoin('users', 'users.teacher_id', '=', 'teachers.id')
            ->select('teachers.*', 'users.email as login_email', 'users.password_state');

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->whereLike('teachers.full_name', '%'.$search.'%')->orWhereLike('teachers.email', '%'.$search.'%');
            });
        }

        return view('admin.teachers.index', [
            'teachers' => $query->orderBy('teachers.full_name')->paginate(12)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function edit(int $teacher): View
    {
        $record = DB::table('teachers')->leftJoin('users', 'users.teacher_id', '=', 'teachers.id')
            ->where('teachers.id', $teacher)->firstOrFail(['teachers.*', 'users.email as login_email']);

        return view('admin.teachers.password', ['teacher' => $record]);
    }

    public function update(Request $request, int $teacher, TeacherAccounts $accounts): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['bail', 'required', 'string', 'min:10', 'regex:/\A[\s\S]{1,72}\z/', 'confirmed'],
        ], [
            'password.required' => 'Hãy nhập mật khẩu tạm.',
            'password.min' => 'Mật khẩu tạm cần ít nhất 10 ký tự.',
            'password.regex' => 'Mật khẩu dài quá giới hạn. Hãy dùng mật khẩu ngắn hơn.',
            'password.confirmed' => 'Hai ô mật khẩu tạm chưa khớp.',
        ]);

        if ($accounts->provision($teacher, $data['password']) === null) {
            throw ValidationException::withMessages(['password' => 'Email của giảng viên đang thuộc một tài khoản khác.']);
        }

        return to_route('admin.teachers.index')->with('status', 'Đã đặt mật khẩu tạm. Giảng viên sẽ phải đổi mật khẩu sau khi đăng nhập.');
    }
}
