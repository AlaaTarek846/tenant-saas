<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyCodeService
{
    public function issueCode(User $user): User
    {
        $user->forceFill([
            'verify_code' => generateVerifyCode(),
            'verify_code_expires_at' => now()->addMinutes(config('verify.expires_minutes', 1)),
        ])->save();

        return $user->fresh();
    }

    public function verify(User $user, string $code): User
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'code' => ['حسابك مُفعَّل بالفعل.'],
            ]);
        }

        if (blank($user->verify_code)) {
            throw ValidationException::withMessages([
                'code' => ['لا يوجد رمز تحقق. اطلب رمزاً جديداً.'],
            ]);
        }

        if ($user->verify_code_expires_at && now()->isAfter($user->verify_code_expires_at)) {
            throw ValidationException::withMessages([
                'code' => ['انتهت صلاحية الرمز. اطلب رمزاً جديداً.'],
            ]);
        }

        if ($user->verify_code !== $code) {
            throw ValidationException::withMessages([
                'code' => ['رمز التحقق غير صحيح.'],
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'verify_code' => null,
            'verify_code_expires_at' => null,
        ])->save();

        return $user->fresh();
    }

    public function resend(User $user): User
    {
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'code' => ['حسابك مُفعَّل بالفعل.'],
            ]);
        }

        if ($user->verify_code_expires_at && now()->isBefore($user->verify_code_expires_at)) {
            $seconds = (int) now()->diffInSeconds($user->verify_code_expires_at);

            throw ValidationException::withMessages([
                'code' => ["يمكنك طلب رمز جديد بعد {$seconds} ثانية."],
            ]);
        }

        return $this->issueCode($user);
    }

    public function canResend(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        if (! $user->verify_code_expires_at) {
            return true;
        }

        return now()->isAfter($user->verify_code_expires_at);
    }

    public function resendCooldownSeconds(User $user): int
    {
        if ($this->canResend($user) || ! $user->verify_code_expires_at) {
            return 0;
        }

        if (now()->isBefore($user->verify_code_expires_at)) {
            return (int) now()->diffInSeconds($user->verify_code_expires_at);
        }

        return 0;
    }
}
