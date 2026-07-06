<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\ResendResetCodeAction;
use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Domain\Auth\Actions\SendResetCodeAction;
use App\Domain\Auth\Actions\VerifyResetCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResendResetCodeRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\VerifyResetCodeRequest;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly SendResetCodeAction $sendResetCodeAction,
        private readonly VerifyResetCodeAction $verifyResetCodeAction,
        private readonly ResendResetCodeAction $resendResetCodeAction,
        private readonly ResetPasswordAction $resetPasswordAction,
    ) {
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $this->sendResetCodeAction->execute($request->validated('email'));

        return response()->json(['message' => __('api.reset_code_sent')]);
    }

    public function verify(VerifyResetCodeRequest $request): JsonResponse
    {
        $this->verifyResetCodeAction->execute(
            $request->validated('email'),
            $request->validated('code'),
        );

        return response()->json(['message' => __('api.reset_code_verified')]);
    }

    public function resend(ResendResetCodeRequest $request): JsonResponse
    {
        $this->resendResetCodeAction->execute($request->validated('email'));

        return response()->json(['message' => __('api.reset_code_resent')]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->resetPasswordAction->execute(
            $request->validated('email'),
            $request->validated('password'),
        );

        return response()->json(['message' => __('api.password_reset')]);
    }
}
