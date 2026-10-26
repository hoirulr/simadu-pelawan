<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\AuditLoggerService;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function handle(Failed $event): void
    {
        $credentials = $event->credentials;
        $attemptedEmail = $credentials['email'] ?? 'unknown';

        $this->auditLogger->log(
            action: 'auth.login_failed',
            subject: $event->user,
            oldValues: null,
            newValues: [
                'attempted_email' => $attemptedEmail,
            ],
            actor: $event->user
        );
    }
}
