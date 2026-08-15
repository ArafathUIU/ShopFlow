<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\IndexUsersRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(IndexUsersRequest $request): JsonResponse
    {
        $query = User::query()
            ->withCount(['orders', 'wishlists'])
            ->withSum('orders', 'total');

        // Filter by role
        if ($request->filled('role')) {
            $role = $request->string('role');
            if (UserRole::tryFrom($role)) {
                $query->where('role', UserRole::from($role));
            }
        }

        // Filter by status
        if ($request->boolean('active') !== null) {
            $isActive = $request->boolean('active');
            if ($isActive) {
                $query->whereNull('deactivated_at');
            } else {
                $query->whereNotNull('deactivated_at');
            }
        }

        // Filter by email verification
        if ($request->boolean('verified') !== null) {
            $isVerified = $request->boolean('verified');
            if ($isVerified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = '%' . $request->string('search') . '%';
            $query->where('name', 'like', $search)
                ->orWhere('email', 'like', $search)
                ->orWhere('phone', 'like', $search);
        }

        $users = $query->latest('created_at')->paginate(24);

        return ApiResponse::success(
            AdminUserResource::collection($users),
            'OK',
            200,
            ['pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]],
        );
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'orders' => fn ($q) => $q->latest('placed_at')->limit(10),
            'wishlists' => fn ($q) => $q->latest('created_at'),
        ])
            ->loadCount(['orders', 'wishlists'])
            ->loadSum('orders', 'total');

        return ApiResponse::success(
            new AdminUserResource($user),
            'User details retrieved.',
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return ApiResponse::success(
            new AdminUserResource($user),
            'User updated.',
        );
    }

    public function deactivate(User $user): JsonResponse
    {
        if ($user->isAdmin() && User::whereRole(UserRole::Admin)->whereNull('deactivated_at')->count() === 1) {
            return ApiResponse::error(
                'Cannot deactivate the last admin user.',
                'LAST_ADMIN_ERROR',
                422,
            );
        }

        $user->deactivated_at = now();
        $user->save();

        return ApiResponse::success(
            new AdminUserResource($user),
            'User deactivated.',
        );
    }

    public function activate(User $user): JsonResponse
    {
        $user->deactivated_at = null;
        $user->save();

        return ApiResponse::success(
            new AdminUserResource($user),
            'User activated.',
        );
    }
}
