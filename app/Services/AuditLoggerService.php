<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLoggerService
{
    /**
     * Catat aktivitas penting pengguna ke database audit_logs dan log file sistem.
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null,
        ?Request $request = null
    ): AuditLog {
        $actorUser = $actor ?? Auth::user();
        $actorId = $actorUser?->id;

        $currentRequest = $request ?? (request() instanceof Request ? request() : null);
        $ipAddress = $currentRequest?->ip();
        $userAgent = $currentRequest?->userAgent();

        $audit = AuditLog::create([
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        Log::info("AUDIT: {$action}", [
            'audit_id' => $audit->id,
            'actor_id' => $actorId,
            'actor_name' => $actorUser?->name,
            'action' => $action,
            'subject_type' => $audit->subject_type,
            'subject_id' => $audit->subject_id,
            'ip_address' => $ipAddress,
        ]);

        return $audit;
    }
}
