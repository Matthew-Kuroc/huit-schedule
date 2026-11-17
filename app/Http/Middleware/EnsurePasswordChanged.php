<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_state === User::PASSWORD_TEMPORARY) {
            abort_if($request->expectsJson(), 403, 'Cần đổi mật khẩu tạm trước khi tiếp tục.');

            return to_route('account.password.edit');
        }

        return $next($request);
    }
}
