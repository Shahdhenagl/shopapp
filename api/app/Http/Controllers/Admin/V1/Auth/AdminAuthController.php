<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1\Auth;

use App\Domain\Admin\Actions\AdminLoginAction;
use App\Domain\Admin\Models\AdminUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Auth\AdminLoginRequest;
use App\Http\Resources\Admin\AdminUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminLoginAction $loginAction,
    ) {
    }

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->execute(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'token' => $result['token'],
            'admin' => AdminUserResource::make($result['admin'])->resolve($request),
        ], 200);
    }

    public function logout(Request $request): Response
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        $admin->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function me(Request $request): AdminUserResource
    {
        return AdminUserResource::make($request->user());
    }
}
