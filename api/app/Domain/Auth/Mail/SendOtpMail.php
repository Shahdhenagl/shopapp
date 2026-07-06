<?php

declare(strict_types=1);

namespace App\Domain\Auth\Mail;

use App\Domain\Auth\Models\PasswordResetCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class SendOtpMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $ttlMinutes,
        public readonly string $purpose = PasswordResetCode::PURPOSE_PASSWORD_RESET,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isVerification()
                ? __('api.verification_code_subject')
                : __('api.reset_code_subject'),
        );
    }

    public function content(): Content
    {
        $intro = $this->isVerification()
            ? __('api.verification_code_intro')
            : __('api.reset_code_intro');

        return new Content(
            htmlString: sprintf(
                '<p>%s <strong>%s</strong>.</p>'
                .'<p>%s</p>',
                e($intro),
                e($this->code),
                e(__('api.otp_expiry_notice', ['minutes' => $this->ttlMinutes])),
            ),
        );
    }

    private function isVerification(): bool
    {
        return $this->purpose === PasswordResetCode::PURPOSE_EMAIL_VERIFICATION;
    }
}
