<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EwsStatus;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use App\Services\AuditLoggerService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VerifyTindakLanjutAction
{
    /**
     * Eksekusi verifikasi tuntas tiket tindak lanjut oleh Admin Dinas.
     *
     * @throws DomainException
     */
    public function execute(TindakLanjutMonitoring $ticket, User $admin): TindakLanjutMonitoring
    {
        Gate::forUser($admin)->authorize('verify', $ticket);

        if (! $admin->isAdmin()) {
            throw new DomainException('Verifikasi tiket tindak lanjut hanya berwenang dilakukan oleh Admin Dinas DP2KBP3A.');
        }

        return DB::transaction(function () use ($ticket, $admin) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'Verified',
                'waktu_selesai_verifikasi' => Carbon::now(),
                'verified_by' => $admin->id,
            ]);

            // EWS hanya 'Resolved' setelah tiket diverifikasi
            if ($ticket->ewsAlert) {
                $ticket->ewsAlert->update([
                    'status_ews' => EwsStatus::Resolved,
                ]);
            }

            app(AuditLoggerService::class)->log(
                'tiket.verify',
                $ticket,
                ['status' => $oldStatus],
                [
                    'status' => 'Verified',
                    'verified_by' => $admin->id,
                    'ews_status' => $ticket->ewsAlert ? EwsStatus::Resolved->value : null,
                ],
                $admin
            );

            return $ticket->fresh(['laporanAnggaran', 'ewsAlert', 'verifikatorUser']);
        });
    }
}
