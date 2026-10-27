<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Models\DokumenLaporan;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\User;
use App\Services\AuditLoggerService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class SubmitLaporanAnggaranAction
{
    /**
     * Eksekusi pengajuan atau pembaruan laporan anggaran balai.
     *
     * @throws DomainException
     */
    public function execute(
        int $balaiId,
        int $periodeId,
        int|float|string $rencanaAnggaran,
        int|float|string $realisasiAnggaran,
        ?string $catatanBalai,
        User $actor
    ): LaporanAnggaran {
        $validated = Validator::make([
            'balai_id' => $balaiId,
            'periode_id' => $periodeId,
            'rencana_anggaran' => $rencanaAnggaran,
            'realisasi_anggaran' => $realisasiAnggaran,
            'catatan_balai' => $catatanBalai,
        ], [
            'balai_id' => ['required', 'integer', 'exists:balai_penyuluh,id'],
            'periode_id' => ['required', 'integer', 'exists:periode_anggaran,id'],
            'rencana_anggaran' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'realisasi_anggaran' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'lte:rencana_anggaran'],
            'catatan_balai' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        if ($actor->isOperatorBalai() && (int) $actor->balai_id !== (int) $validated['balai_id']) {
            throw new AuthorizationException('Operator hanya dapat mengirim laporan untuk balainya sendiri.');
        }

        $balaiId = (int) $validated['balai_id'];
        $periodeId = (int) $validated['periode_id'];
        $rencanaAnggaran = (float) $validated['rencana_anggaran'];
        $realisasiAnggaran = (float) $validated['realisasi_anggaran'];
        $catatanBalai = $validated['catatan_balai'] ?? null;

        $laporan = LaporanAnggaran::firstOrNew([
            'balai_id' => $balaiId,
            'periode_id' => $periodeId,
        ]);

        if ($laporan->exists) {
            Gate::forUser($actor)->authorize('update', $laporan);

            // Guard status transisi: Laporan yang telah diverifikasi tuntas tidak boleh diubah
            if ($laporan->status_laporan === ReportStatus::Verified) {
                throw new DomainException('Laporan telah terverifikasi tuntas dan tidak dapat diubah tanpa pembukaan revisi oleh Admin Dinas.');
            }
        } else {
            Gate::forUser($actor)->authorize('create', LaporanAnggaran::class);
        }

        $serapan = $rencanaAnggaran > 0
            ? round(($realisasiAnggaran / $rencanaAnggaran) * 100, 2)
            : 0.0;

        return DB::transaction(function () use ($laporan, $periodeId, $rencanaAnggaran, $realisasiAnggaran, $serapan, $catatanBalai, $actor) {
            $isFirstSubmission = ! $laporan->exists || ! $laporan->tgl_submit_pertama;

            $laporan->rencana_anggaran = $rencanaAnggaran;
            $laporan->realisasi_anggaran = $realisasiAnggaran;
            $laporan->persentase_serapan = $serapan;
            $laporan->catatan_balai = $catatanBalai;
            $laporan->status_laporan = ReportStatus::Submitted;
            $laporan->save();

            // Inisialisasi daftar dokumen checklist jika belum ada
            if ($laporan->dokumenLaporan()->count() === 0) {
                $periode = PeriodeAnggaran::find($periodeId);
                $dokWajib = $periode?->dokumen_wajib_json ?? [
                    'Laporan Realisasi Anggaran (LRA)',
                    'Surat Pertanggungjawaban Mutlak (SPTJM)',
                    'Buku Kas Umum & Kuitansi',
                    'Dokumentasi Kegiatan',
                    'Rencana Kebutuhan Tahap II',
                ];

                foreach ($dokWajib as $namaDok) {
                    DokumenLaporan::create([
                        'laporan_id' => $laporan->id,
                        'nama_dokumen_wajib' => $namaDok,
                        'status_validasi' => 'kurang',
                    ]);
                }
            }

            // Kunci timestamp pengiriman pertama
            if ($isFirstSubmission) {
                $allComplete = $laporan->dokumenLaporan()->where('status_validasi', '!=', 'lengkap')->count() === 0
                    && $laporan->dokumenLaporan()->count() > 0;

                $laporan->update([
                    'tgl_submit_pertama' => Carbon::now(),
                    'lengkap_pengiriman_pertama' => $allComplete,
                ]);
            }

            app(AuditLoggerService::class)->log(
                'laporan.submit',
                $laporan,
                $isFirstSubmission ? null : ['status_laporan' => $laporan->getOriginal('status_laporan')],
                [
                    'rencana_anggaran' => $rencanaAnggaran,
                    'realisasi_anggaran' => $realisasiAnggaran,
                    'persentase_serapan' => $serapan,
                    'status_laporan' => ReportStatus::Submitted->value,
                ],
                $actor
            );

            return $laporan->fresh(['dokumenLaporan', 'balaiPenyuluh', 'periodeAnggaran']);
        });
    }
}
