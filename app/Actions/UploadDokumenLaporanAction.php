<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReportStatus;
use App\Models\DokumenLaporan;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadDokumenLaporanAction
{
    /**
     * Eksekusi penyimpanan berkas dokumen laporan ke private storage.
     *
     * @throws DomainException
     */
    public function execute(DokumenLaporan $dokumen, UploadedFile $file, User $actor): DokumenLaporan
    {
        $dokumen->loadMissing('laporanAnggaran');
        Gate::forUser($actor)->authorize('upload', $dokumen);

        $laporan = $dokumen->laporanAnggaran;
        if ($laporan && $laporan->status_laporan === ReportStatus::Verified && ! $actor->isAdmin()) {
            throw new DomainException('Laporan telah berstatus Terverifikasi Tuntas sehingga berkas dokumen tidak dapat diganti.');
        }

        return DB::transaction(function () use ($dokumen, $file) {
            // Hapus berkas lama jika ada di storage private
            if ($dokumen->file_path && Storage::disk('local')->exists($dokumen->file_path)) {
                Storage::disk('local')->delete($dokumen->file_path);
            }

            $ext = $file->getClientOriginalExtension() ?: 'pdf';
            $storedName = Str::uuid()->toString().'.'.$ext;
            $path = $file->storeAs('dokumen_laporan/'.$dokumen->laporan_id, $storedName, 'local');

            $dokumen->update([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'status_validasi' => 'lengkap',
            ]);

            return $dokumen->fresh();
        });
    }
}
