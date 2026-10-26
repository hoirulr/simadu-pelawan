<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DokumenLaporan;
use App\Models\TindakLanjutMonitoring;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    /**
     * Download an uploaded report document securely with authorization.
     */
    public function downloadDokumenLaporan(DokumenLaporan $dokumen): StreamedResponse
    {
        Gate::authorize('download', $dokumen);

        if (! $dokumen->file_path || ! Storage::disk('local')->exists($dokumen->file_path)) {
            abort(404, 'Berkas dokumen tidak ditemukan di server penyimpanan.');
        }

        $downloadName = $dokumen->file_name ?: 'dokumen-laporan.pdf';

        return Storage::disk('local')->download($dokumen->file_path, $downloadName);
    }

    /**
     * Download follow-up correction evidence securely with authorization.
     */
    public function downloadBuktiKoreksi(TindakLanjutMonitoring $ticket): StreamedResponse
    {
        Gate::authorize('downloadEvidence', $ticket);

        if (! $ticket->file_bukti_koreksi || ! Storage::disk('local')->exists($ticket->file_bukti_koreksi)) {
            abort(404, 'Berkas bukti perbaikan tidak ditemukan di server penyimpanan.');
        }

        $downloadName = basename($ticket->file_bukti_koreksi);

        return Storage::disk('local')->download($ticket->file_bukti_koreksi, $downloadName);
    }
}
