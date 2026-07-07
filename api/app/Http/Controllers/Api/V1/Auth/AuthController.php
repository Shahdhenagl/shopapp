<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\ConfirmEmailVerificationAction;
use App\Domain\Auth\Actions\LoginAction;
use App\Domain\Auth\Actions\LogoutAction;
use App\Domain\Auth\Actions\RefreshAccessTokenAction;
use App\Domain\Auth\Actions\RegisterAction;
use App\Domain\Auth\Actions\SendEmailVerificationAction;
use App\Domain\Auth\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ConfirmEmailRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterAction $registerAction,
        private readonly LoginAction $loginAction,
        private readonly LogoutAction $logoutAction,
        private readonly RefreshAccessTokenAction $refreshAccessTokenAction,
        private readonly SendEmailVerificationAction $sendEmailVerificationAction,
        private readonly ConfirmEmailVerificationAction $confirmEmailVerificationAction,
    ) {
    }

    /**
     * Instant sign-up: creates the (unverified) account and signs the user in.
     * Email verification is soft and enforced only at checkout.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->registerAction->execute(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('phone'),
            $request->validated('password'),
        );

        return response()->json([
            'token' => $result->token,
            'refresh_token' => $result->refreshToken,
            'user' => UserResource::make($result->user)->resolve($request),
        ], 201);
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
        $this->logoutAction->execute($this->user($request));

        return response()->noContent();
    }

    /**
     * (Re)send the soft email-verification code to the authenticated user.
     */
    public function sendEmailVerification(Request $request): Response
    {
        $this->sendEmailVerificationAction->execute($this->user($request));

        return response()->noContent();
    }

    /**
     * Confirm the email-verification code; returns the updated user.
     */
    public function verifyEmail(ConfirmEmailRequest $request): JsonResponse
    {
        $user = $this->confirmEmailVerificationAction->execute(
            $this->user($request),
            $request->validated('code'),
        );

        return response()->json([
            'user' => UserResource::make($user)->resolve($request),
        ]);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
