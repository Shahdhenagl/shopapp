<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Actions\LogoutAction;
use App\Domain\Auth\Actions\RefreshAccessTokenAction;
use App\Domain\Auth\Actions\RegisterAction;
use App\Domain\Auth\Actions\ResendVerificationAction;
use App\Domain\Auth\Actions\VerifyEmailAction;
use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResendVerificationRequest;
use App\Http\Requests\Api\V1\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterAction $registerAction,
        private readonly VerifyEmailAction $verifyEmailAction,
        private readonly ResendVerificationAction $resendVerificationAction,
        private readonly LoginAction $loginAction,
        private readonly LogoutAction $logoutAction,
        private readonly RefreshAccessTokenAction $refreshAccessTokenAction,
    ) {
    }

    /**
     * Sign-up is OTP-gated: creates an unverified account, emails a 4-digit
     * code, and returns NO token (BACKEND.md §4).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $this->registerAction->execute(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('phone'),
            $request->validated('password'),
        );

        return response()->json([
            'message' => __('api.verification_code_sent'),
        ], 201);
    }

    /**
     * Confirms the sign-up OTP and signs the user in (token pair + user).
     */
    public function verifyRegistration(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->verifyEmailAction->execute(
            $request->validated('email'),
            $request->validated('code'),
        );

        return response()->json([
            'token' => $result->token,
            'refresh_token' => $result->refreshToken,
            'user' => UserResource::make($result->user)->resolve($request),
        ], 200);
    }

    /**
     * Re-sends the sign-up OTP. Always 200 (never leaks account existence).
     */
    public function resendRegistration(ResendVerificationRequest $request): JsonResponse
    {
        $this->resendVerificationAction->execute($request->validated('email'));

        return response()->json([
            'message' => __('api.verification_code_resent'),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->execute(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json([
            'token' => $result->token,
            'refresh_token' => $result->refreshToken,
            'user' => UserResource::make($result->user)->resolve($request),
        ], 200);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->refreshAccessTokenAction->execute(
            $request->validated('refresh_token'),
        );

        return response()->json([
            'token' => $result->token,
            'refresh_token' => $result->refreshToken,
            'user' => UserResource::make($result->user)->resolve($request),
        ], 200);
    }

    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->logoutAction->execute($user);

        return response()->noContent();
    }
}
