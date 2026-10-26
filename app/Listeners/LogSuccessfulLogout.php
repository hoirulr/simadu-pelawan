<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLoggerService;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct(
        protected AuditLoggerService $auditLogger
    ) {}

    public function handle(Logout $event): void
    {
        /** @var User|null $user */
        $user = $event->user;

        if ($user) {
            $this->auditLogger->log(
                action: 'auth.logout',
                subject: $user,
                oldValues: [
                    'email' => $user->email,
                    'role' => $user->role?->value ?? (string) $user->role,
                ],
                newValues: null,
                actor: $user
            );
        }
    }
}
