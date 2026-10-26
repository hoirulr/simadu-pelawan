<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->auditLogger->log(
            action: 'auth.login',
            subject: $user,
            oldValues: null,
            newValues: [
                'email' => $user->email,
                'role' => $user->role?->value ?? (string) $user->role,
                'balai_id' => $user->balai_id,
            ],
            actor: $user
        );
    }
}
