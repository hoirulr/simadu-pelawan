<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BalaiPenyuluh;
use App\Models\PeriodeAnggaran;
use App\Services\EconomicValueCalculatorService;
use App\Services\PdfReportGeneratorService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReportExportController extends Controller
{
    /**
     * Download dokumen resmi Rekapitulasi Nilai Ekonomis & Kinerja Balai dalam format PDF.
     */
    public function downloadEconomicEvaluationPdf(
        PeriodeAnggaran $periode,
        EconomicValueCalculatorService $calculator,
        PdfReportGeneratorService $pdfService
    ): Response {
        Gate::authorize('exportPdf', $periode);

        $economicStats = $calculator->calculate($periode->id);

        $balaiList = BalaiPenyuluh::with([
            'laporanAnggaran' => function ($q) use ($periode) {
                $q->where('periode_id', $periode->id)
                    ->with(['skoringRisiko', 'ewsAlerts', 'tindakLanjutMonitoring']);
            },
        ])->orderBy('id', 'asc')->get();

        $pdfContent = $pdfService->generateEconomicReportPdf($periode, $economicStats, $balaiList);
        $filename = "Rekapitulasi_Nilai_Ekonomis_{$periode->tahun}_{$periode->tahap}.pdf";

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($pdfContent),
            'Cache-Control' => 'private, no-transform, no-store, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }
}
