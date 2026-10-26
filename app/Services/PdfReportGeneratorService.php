<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PeriodeAnggaran;
use Carbon\Carbon;

class PdfReportGeneratorService
{
    /**
     * Hasilkan dokumen binary PDF 1.4 resmi untuk Rekapitulasi Nilai Ekonomis SIMADU PELAWAN.
     */
    public function generateEconomicReportPdf(PeriodeAnggaran $periode, array $economicStats, $balaiList): string
    {
        $lines = [];

        // Header / Kop Dokumen
        $lines[] = ['font' => 'F2', 'size' => 12, 'x' => 50, 'y' => 790, 'text' => 'PEMERINTAH KABUPATEN BANGKA'];
        $lines[] = ['font' => 'F2', 'size' => 14, 'x' => 50, 'y' => 772, 'text' => 'DINAS PENGENDALIAN PENDUDUK, KB, PEMBERDAYAAN PEREMPUAN & PERLINDUNGAN ANAK'];
        $lines[] = ['font' => 'F1', 'size' => 9, 'x' => 50, 'y' => 758, 'text' => 'Jalan Ahmad Yani, Sungailiat, Kabupaten Bangka, Kepulauan Bangka Belitung (33215)'];
        $lines[] = ['font' => 'F2', 'size' => 11, 'x' => 50, 'y' => 735, 'text' => "REKAPITULASI EVALUASI NILAI EKONOMIS & AKUNTABILITAS ANGGARAN — TAHUN {$periode->tahun} ({$periode->tahap})"];
        $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 50, 'y' => 722, 'text' => 'Dicetak pada: '.Carbon::now()->translatedFormat('d F Y H:i:s').' WIB | Sistem: SIMADU PELAWAN'];

        // Garis Pembatas
        // Draw decorative lines
        $drawCommands = "0.5 w\n";
        $drawCommands .= "50 750 m 545 750 l S\n";
        $drawCommands .= "1.5 w\n";
        $drawCommands .= "50 748 m 545 748 l S\n";

        // Ringkasan 5 Indikator Nilai Ekonomis
        $lines[] = ['font' => 'F2', 'size' => 10, 'x' => 50, 'y' => 695, 'text' => 'I. RINGKASAN 5 INDIKATOR NILAI EKONOMIS INOVASI'];

        $savedAmount = number_format((float) ($economicStats['anggaran_terselamatkan'] ?? 0), 0, ',', '.');
        $timeSaved = $economicStats['waktu_efisiensi_hari'] ?? 0;
        $accPercent = $economicStats['persentase_akuntabilitas'] ?? 0;
        $resPercent = $economicStats['persentase_resolusi_ews'] ?? 0;
        $riskCount = $economicStats['total_balai_risiko_tinggi'] ?? 0;

        $lines[] = ['font' => 'F1', 'size' => 9, 'x' => 60, 'y' => 678, 'text' => "1. Nilai Anggaran Terlindungi dari Risiko Penundaan  : Rp {$savedAmount}"];
        $lines[] = ['font' => 'F1', 'size' => 9, 'x' => 60, 'y' => 663, 'text' => "2. Efisiensi Percepatan Waktu Verifikasi Berkas SPJ   : {$timeSaved} Hari Kalender"];
        $lines[] = ['font' => 'F1', 'size' => 9, 'x' => 60, 'y' => 648, 'text' => "3. Indeks Kepatuhan Akuntabilitas 8 Balai KB          : {$accPercent}% (Standar Baik)"];
        $lines[] = ['font' => 'F1', 'size' => 9, 'x' => 60, 'y' => 633, 'text' => "4. Rasio Penyelesaian Tiket Peringatan Dini (EWS)    : {$resPercent}% Tuntas Terverifikasi"];
        $lines[] = ['font' => 'F1', 'size' => 9, 'x' => 60, 'y' => 618, 'text' => "5. Mitigasi Balai Berisiko Tinggi (Level Kritis)     : {$riskCount} Balai KB"];

        // Tabel Rekapitulasi 8 Balai
        $lines[] = ['font' => 'F2', 'size' => 10, 'x' => 50, 'y' => 590, 'text' => 'II. MATRIKS KINERJA & AKUNTABILITAS 8 BALAI PENYULUH KB'];

        // Table Header
        $drawCommands .= "0.5 w\n";
        $drawCommands .= "50 580 m 545 580 l S\n";
        $drawCommands .= "50 562 m 545 562 l S\n";

        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 55, 'y' => 568, 'text' => 'No'];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 75, 'y' => 568, 'text' => 'Nama Balai Penyuluh KB'];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 220, 'y' => 568, 'text' => 'Serapan Anggaran'];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 310, 'y' => 568, 'text' => 'Skor Risiko'];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 380, 'y' => 568, 'text' => 'Kategori Risiko'];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 470, 'y' => 568, 'text' => 'Status SPJ'];

        $currentY = 548;
        $no = 1;

        foreach ($balaiList as $balai) {
            $laporan = $balai->laporanAnggaran->first();
            $serapan = $laporan ? number_format((float) $laporan->persentase_serapan, 1, ',', '.').'%' : '0,0%';
            $skor = $laporan?->skoringRisiko ? (string) $laporan->skoringRisiko->total_skor : '-';
            $kategori = $laporan?->skoringRisiko?->kategori_risiko ? $laporan->skoringRisiko->kategori_risiko->value : 'Belum Dinilai';
            $status = $laporan?->status_laporan ? $laporan->status_laporan->label() : 'Belum Lapor';

            $cleanBalai = substr($balai->nama_balai, 0, 26);
            $cleanStatus = substr($status, 0, 15);

            $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 55, 'y' => $currentY, 'text' => (string) $no];
            $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 75, 'y' => $currentY, 'text' => $cleanBalai];
            $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 220, 'y' => $currentY, 'text' => $serapan];
            $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 310, 'y' => $currentY, 'text' => $skor];
            $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 380, 'y' => $currentY, 'text' => $kategori];
            $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 470, 'y' => $currentY, 'text' => $cleanStatus];

            $lineY = $currentY - 4;
            $drawCommands .= "50 {$lineY} m 545 {$lineY} l S\n";

            $currentY -= 18;
            $no++;
        }

        // Tanda Tangan Pengesahan
        $signY = $currentY - 30;
        $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 380, 'y' => $signY, 'text' => 'Sungailiat, '.Carbon::now()->translatedFormat('d F Y')];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 380, 'y' => $signY - 14, 'text' => 'Kepala Dinas DP2KBP3A Kab. Bangka'];
        $lines[] = ['font' => 'F2', 'size' => 8, 'x' => 380, 'y' => $signY - 60, 'text' => 'Drs. H. NURHITA, M.M.'];
        $lines[] = ['font' => 'F1', 'size' => 8, 'x' => 380, 'y' => $signY - 72, 'text' => 'NIP. 19680512 199403 1 004'];

        // Build PDF Text Stream
        $contentStream = "q\n".$drawCommands."Q\n";
        $contentStream .= "BT\n";
        foreach ($lines as $item) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $item['text']);
            $contentStream .= "/{$item['font']} {$item['size']} Tf\n";
            $contentStream .= "{$item['x']} {$item['y']} Td\n";
            $contentStream .= "({$escaped}) Tj\n";
            $contentStream .= "-{$item['x']} -{$item['y']} Td\n";
        }
        $contentStream .= "ET\n";

        return $this->assemblePdfDocument($contentStream, "Rekapitulasi Nilai Ekonomis {$periode->tahun}");
    }

    /**
     * Rakit struktur biner PDF 1.4 lengkap dengan objek catalog, pages, fonts, dan metadata.
     */
    protected function assemblePdfDocument(string $stream, string $title): string
    {
        $streamLen = strlen($stream);
        $now = Carbon::now()->format('YmdHis');

        $objects = [];

        // 1: Catalog
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        // 2: Pages
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';

        // 3: Page (A4: 595.28 x 841.89)
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>';

        // 4: Contents Stream
        $objects[4] = "<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream";

        // 5: Font F1 (Helvetica)
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        // 6: Font F2 (Helvetica-Bold)
        $objects[6] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        // 7: Metadata Info
        $objects[7] = "<< /Title ({$title}) /Author (DP2KBP3A Kabupaten Bangka) /Creator (SIMADU PELAWAN) /CreationDate (D:{$now}+07'00') >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$obj}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= '0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n";
        $pdf .= '<< /Size '.(count($objects) + 1)." /Root 1 0 R /Info 7 0 R >>\n";
        $pdf .= "startxref\n";
        $pdf .= "{$xrefOffset}\n";
        $pdf .= "%%EOF\n";

        return $pdf;
    }
}
