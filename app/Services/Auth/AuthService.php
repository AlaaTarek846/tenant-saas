<?php

namespace App\Services\Auth;

use App\Enums\UserStatusEnum;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Admin\UserRepository;
use App\Services\Admin\ChartOfAccountsService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepository $userRepository,
        protected VerifyCodeService $verifyCodeService,
        protected ChartOfAccountsService $chartOfAccountsService,
    ) {}

    /**
     * @return array{user: UserResource}
     */
    public function login(array $credentials, bool $remember = false): array
    {
        $email = $credentials['email'];
        $password = $credentials['password'];

        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الدخول غير صحيحة.'],
            ]);
        }

        $this->ensureUserIsActive($user);

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            throw ValidationException::withMessages([
                'email' => ['تعذر تسجيل الدخول. حاول مرة أخرى.'],
            ]);
        }

        request()->session()->regenerate();

        return [
            'user' => new UserResource(Auth::user()->load('tenant')),
        ];
    }

    /**
     * @return array{user: UserResource}
     */
    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['company']['name'],
                'email' => $data['company']['email'],
                'country' => $data['company']['country'] ?? null,
                'city' => $data['company']['city'] ?? null,
                'phone' => $data['company']['phone'] ?? null,
                'status' => UserStatusEnum::ACTIVE,
            ]);

            $user = $this->userRepository->createUser([
                'name' => $data['admin']['name'],
                'email' => $data['admin']['email'],
                'password' => $data['admin']['password'],
                'tenant_id' => $tenant->id,
                'is_owner' => true,
                'status' => UserStatusEnum::ACTIVE,
            ]);

            $user->assignRole('Company_Admin');

            $this->chartOfAccountsService->seedForTenant($tenant);

            return $user;
        });

        $this->verifyCodeService->issueCode($user);

        event(new Registered($user));

        Auth::login($user);
        request()->session()->regenerate();

        return [
            'user' => new UserResource($user->fresh()->load('tenant')),
        ];
    }

    public function verifyCode(User $user, string $code): UserResource
    {
        $verifiedUser = $this->verifyCodeService->verify($user, $code);

        return new UserResource($verifiedUser->load('tenant'));
    }

    public function resendVerifyCode(User $user): UserResource
    {
        $updatedUser = $this->verifyCodeService->resend($user);

        return new UserResource($updatedUser->load('tenant'));
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function user(Request $request): UserResource
    {
        return new UserResource($request->user()->load('tenant'));
    }

    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.';
        }

        if ($status === Password::INVALID_USER) {
            return 'إذا كان البريد مسجلاً لدينا، ستتلقى رابط إعادة التعيين قريباً.';
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $this->userRepository->update($user->id, [
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ]);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return 'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.';
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    public function resendVerificationEmail(Request $request): void
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني مُؤكَّد بالفعل.'],
            ]);
        }

        $user->sendEmailVerificationNotification();
    }

    protected function ensureUserIsActive(User $user): void
    {
        if ($user->status !== UserStatusEnum::ACTIVE) {
            throw ValidationException::withMessages([
                'email' => ['حسابك غير نشط. تواصل مع الإدارة.'],
            ]);
        }

        if ($user->tenant_id) {
            $user->loadMissing('tenant');

            if ($user->tenant && $user->tenant->status !== UserStatusEnum::ACTIVE) {
                throw ValidationException::withMessages([
                    'email' => ['تم إيقاف شركتك. تواصل مع الإدارة.'],
                ]);
            }
        }
    }
}
