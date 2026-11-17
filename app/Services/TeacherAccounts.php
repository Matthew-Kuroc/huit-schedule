<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class TeacherAccounts
{
    /**
     * Return true for an account created or updated, false for an account already provisioned,
     * or null when the teacher's email belongs to another account.
     */
    public function provision(int $teacherId, #[\SensitiveParameter] ?string $temporaryPassword = null): ?bool
    {
        return DB::transaction(function () use ($teacherId, $temporaryPassword): ?bool {
            $teacher = DB::table('teachers')->where('id', $teacherId)->lockForUpdate()->firstOrFail();
            $user = User::where('teacher_id', $teacherId)->lockForUpdate()->first();

            if ($user === null) {
                if (User::where('email', $teacher->email)->exists()) {
                    return null;
                }

                $user = new User(['email' => $teacher->email, 'name' => $teacher->full_name]);
                $user->teacher_id = $teacherId;
            } elseif ($temporaryPassword === null) {
                return false;
            } elseif ($user->is_admin) {
                return null;
            }

            $user->forceFill([
                'password' => $temporaryPassword ?? Str::random(48),
                'password_state' => $temporaryPassword === null ? User::PASSWORD_UNISSUED : User::PASSWORD_TEMPORARY,
                'remember_token' => Str::random(60),
            ])->save();

            if ($temporaryPassword !== null) {
                Password::deleteToken($user);
            }

            return true;
        }, 3);
    }
}
