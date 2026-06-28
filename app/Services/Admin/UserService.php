<?php

namespace App\Services\Admin;

use App\Enums\UserStatusEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\Admin\RoleRepository;
use App\Repositories\Admin\UserRepository;
use App\Services\BaseService;
use App\Support\AdminPermissions;
use App\Support\TenantScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class UserService extends BaseService
{
    public function __construct(
        UserRepository $repository,
        protected RoleRepository $roleRepository,
        protected TenantService $tenantService,
    ) {
        $this->repository = $repository;
    }

    public function allOrPaginate($resource, $groupBy = null, ?User $actor = null)
    {
        $query = $this->scopedQuery($actor);

        if ($search = request('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        $query->latest('id');

        return allOrPaginate($query, $resource, $groupBy);
    }

    public function store(array $data, ?User $actor = null): User
    {
        $payload = $this->preparePayload($data, $actor);
        $roles = $payload['roles'] ?? [];
        unset($payload['roles']);

        $user = $this->repository->createUser($payload);

        if ($roles !== []) {
            $this->repository->syncRoles($user, $this->filterAssignableRoles($roles, $actor, $user));
        }

        return $user->load('roles');
    }

    public function show($id, ?User $actor = null): User
    {
        $user = $this->repository->show($id);
        $this->ensureCanAccess($user, $actor);

        return $user->load('roles');
    }

    public function update($id, array $data, ?User $actor = null): User
    {
        $user = $this->repository->show($id);
        $this->ensureCanAccess($user, $actor);
        $this->ensureNotSelfDemotion($user, $actor, $data);

        $payload = $this->preparePayload($data, $actor, $user);
        $roles = $payload['roles'] ?? null;
        unset($payload['roles']);

        if (isset($payload['password']) && blank($payload['password'])) {
            unset($payload['password']);
        }

        $user = $this->repository->update($id, $payload);

        if (is_array($roles)) {
            $this->repository->syncRoles($user, $this->filterAssignableRoles($roles, $actor, $user));
        }

        return $user->load('roles');
    }

    public function destroy($id, ?User $actor = null, ?string $action = null): bool
    {
        $user = $this->repository->show($id);
        $this->ensureCanAccess($user, $actor);

        if ($actor && $actor->id === $user->id) {
            throw ValidationException::withMessages([
                'user' => ['لا يمكنك حذف حسابك.'],
            ]);
        }

        if ($this->requiresTenantAction($user, $actor)) {
            return $this->handleOwnerDestroy($user, $action);
        }

        return (bool) $this->repository->destroy($id);
    }

    protected function scopedQuery(?User $actor)
    {
        $this->ensureAdminAccess($actor);

        $tenantId = TenantScope::isScoped($actor) ? TenantScope::id($actor) : null;

        $query = $this->repository->scopedQuery($tenantId);

        if (! TenantScope::isScoped($actor)) {
            $query->role('Company_Admin')
                ->with(['tenant' => fn ($builder) => $builder->withCount('users')]);
        }

        return $query;
    }

    protected function ensureAdminAccess(?User $actor): void
    {
        if ($actor?->hasAnyRole(['Super_Admin', 'Company_Admin'])) {
            return;
        }

        if ($actor?->hasAnyPermission(AdminPermissions::COMPANY)) {
            return;
        }

        throw new AuthorizationException('ليس لديك صلاحية إدارة المستخدمين.');
    }

    protected function ensureCanAccess(User $user, ?User $actor): void
    {
        if (! TenantScope::isScoped($actor)) {
            return;
        }

        if ($user->tenant_id === TenantScope::id($actor)) {
            return;
        }

        throw new AuthorizationException('ليس لديك صلاحية الوصول لهذا المستخدم.');
    }

    protected function preparePayload(array $data, ?User $actor, ?User $existing = null): array
    {
        if (TenantScope::isScoped($actor)) {
            $data['tenant_id'] = TenantScope::id($actor);
        }

        if (! isset($data['status'])) {
            $data['status'] = $existing?->status ?? UserStatusEnum::ACTIVE;
        }

        if (! $existing) {
            $data['email_verified_at'] = now();
        }

        return $data;
    }

    /**
     * @param  list<string>  $roles
     * @return list<string>
     */
    protected function filterAssignableRoles(array $roles, ?User $actor, ?User $target = null): array
    {
        if (! TenantScope::isScoped($actor)) {
            return $roles;
        }

        $allowed = $this->roleRepository
            ->tenantRolesQuery(TenantScope::id($actor))
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();

        if ($target && $actor->id === $target->id && $target->hasRole('Company_Admin')) {
            $allowed[] = 'Company_Admin';
        }

        return array_values(array_unique($allowed));
    }

    protected function ensureNotSelfDemotion(User $user, ?User $actor, array $data): void
    {
        if (! $actor || $actor->id !== $user->id || ! isset($data['roles'])) {
            return;
        }

        if (! in_array('Company_Admin', $data['roles'], true) && $user->hasRole('Company_Admin')) {
            throw ValidationException::withMessages([
                'roles' => ['لا يمكنك إزالة دور مدير الشركة من حسابك.'],
            ]);
        }
    }

    protected function requiresTenantAction(User $user, ?User $actor): bool
    {
        if (TenantScope::isScoped($actor)) {
            return false;
        }

        return $user->is_owner
            && $user->hasRole('Company_Admin')
            && filled($user->tenant_id);
    }

    protected function handleOwnerDestroy(User $user, ?string $action): bool
    {
        if (! in_array($action, ['force', 'suspend'], true)) {
            throw ValidationException::withMessages([
                'action' => ['يجب اختيار نوع الإجراء: حذف نهائي أو إيقاف الشركة.'],
            ]);
        }

        if ($action === 'suspend') {
            $this->tenantService->suspend($user->tenant_id);

            return true;
        }

        return $this->tenantService->forceDestroy($user->tenant_id);
    }
}
