<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyCodeRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated(),
            $request->boolean('remember'),
        );

        return responseJson(200, 'تم تسجيل الدخول بنجاح.', $result);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return responseJson(201, 'تم تسجيل الشركة بنجاح.', $result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return responseJson(200, 'تم تسجيل الخروج بنجاح.');
    }

    public function user(Request $request): JsonResponse
    {
        return responseJson(200, 'بيانات المستخدم.', [
            'user' => $this->authService->user($request),
        ]);
    }

    public function verifyCode(VerifyCodeRequest $request): JsonResponse
    {
        $userResource = $this->authService->verifyCode(
            $request->user(),
            $request->validated('code'),
        );

        return responseJson(200, 'تم تأكيد الحساب بنجاح.', [
            'user' => $userResource,
        ]);
    }

    public function resendVerifyCode(Request $request): JsonResponse
    {
        $userResource = $this->authService->resendVerifyCode($request->user());

        return responseJson(200, 'تم إرسال رمز تحقق جديد.', [
            'user' => $userResource,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->sendPasswordResetLink($request->validated('email'));

        return responseJson(200, $message);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->resetPassword($request->validated());

        return responseJson(200, $message);
    }

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        $request->fulfill();

        return responseJson(200, 'تم تأكيد البريد الإلكتروني بنجاح.', [
            'user' => new UserResource($request->user()->fresh()),
        ]);
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $this->authService->resendVerificationEmail($request);

        return responseJson(200, 'تم إرسال رابط تأكيد البريد الإلكتروني.');
    }
}