<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.admin-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Hãy nhập email.',
            'email.email' => 'Email chưa đúng định dạng.',
            'password.required' => 'Hãy nhập mật khẩu.',
        ]);

        if (! Auth::attemptWhen($credentials, fn (User $user): bool => $user->is_admin || $user->teacher_id !== null)) {
            return back()->withErrors(['email' => 'Email hoặc mật khẩu chưa đúng.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        $request->session()->forget('url.intended');

        return to_route('home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
