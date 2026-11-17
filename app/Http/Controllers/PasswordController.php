<?php

namespace App\Http\Controllers;

use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:255'],
            'password' => [...$this->newPasswordRules(), 'different:current_password'],
        ], $this->passwordMessages());

        $user = DB::transaction(function () use ($request, $data): User {
            $account = User::whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            if (! Hash::check($data['current_password'], $account->password)) {
                throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại chưa đúng.']);
            }

            $this->savePassword($account, $data['password']);
            Password::deleteToken($account);

            return $account;
        });

        Auth::guard('web')->setUser($user);
        $request->session()->regenerate(true);

        return to_route('home')->with('status', $this->notifyPasswordChange($user));
    }

    public function forgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']], $this->passwordMessages());

        try {
            Password::sendResetLink(['email' => $data['email'], $this->eligibleAccount()]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Nếu email thuộc tài khoản đã được cấp mật khẩu, hệ thống sẽ gửi liên kết đặt lại.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:128'],
            'password' => $this->newPasswordRules(),
        ], $this->passwordMessages());

        $notification = null;
        $result = Password::reset($data + [$this->eligibleAccount()], function (User $user, string $password) use (&$notification): void {
            $this->savePassword($user, $password);
            $notification = $user;
        });

        if ($result !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Liên kết đặt lại đã hết hạn, đã được sử dụng hoặc không khớp với email.']);
        }

        return to_route('login')->with('status', $this->notifyPasswordChange($notification));
    }

    private function eligibleAccount(): Closure
    {
        return fn (Builder $query) => $query->where('password_state', '!=', User::PASSWORD_UNISSUED)
            ->where(fn (Builder $roles) => $roles->where('is_admin', true)->orWhereNotNull('teacher_id'));
    }

    private function savePassword(User $user, #[\SensitiveParameter] string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'password_state' => User::PASSWORD_ACTIVE,
            'remember_token' => Str::random(60),
        ])->save();
    }

    private function notifyPasswordChange(User $user): string
    {
        try {
            Mail::raw(
                'Mật khẩu tài khoản HUIT Schedule Hub của bạn đã được cập nhật.',
                fn (Message $message) => $message->to($user->email)->subject('Mật khẩu HUIT Schedule Hub đã thay đổi'),
            );

            return 'Đã cập nhật mật khẩu và gửi thông báo qua email.';
        } catch (Throwable $exception) {
            report($exception);

            return 'Đã cập nhật mật khẩu. Hệ thống chưa gửi được email thông báo.';
        }
    }

    private function newPasswordRules(): array
    {
        return ['bail', 'required', 'string', 'min:10', 'regex:/\A[\s\S]{1,72}\z/', 'confirmed'];
    }

    private function passwordMessages(): array
    {
        return [
            'email.required' => 'Hãy nhập email.',
            'email.email' => 'Email chưa đúng định dạng.',
            'current_password.required' => 'Hãy nhập mật khẩu hiện tại.',
            'password.required' => 'Hãy nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới cần ít nhất 10 ký tự.',
            'password.regex' => 'Mật khẩu dài quá giới hạn. Hãy dùng mật khẩu ngắn hơn.',
            'password.confirmed' => 'Hai ô mật khẩu mới chưa khớp.',
            'password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ];
    }
}
