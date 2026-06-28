<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanyProfileRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Services\Admin\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return responseJson(200, 'تم جلب الملف الشخصي.', $this->profileService->show($request->user()));
    }

    public function updateUser(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateUser($request->user(), $request->validated());

        return responseJson(200, 'تم تحديث بياناتك الشخصية.', [
            'user' => new UserResource($user),
        ]);
    }

    public function updateCompany(UpdateCompanyProfileRequest $request): JsonResponse
    {
        $tenant = $this->profileService->updateCompany($request->user(), $request->validated());

        return responseJson(200, 'تم تحديث بيانات الشركة.', [
            'company' => new TenantResource($tenant),
        ]);
    }
}
