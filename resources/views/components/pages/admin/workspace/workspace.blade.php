<div class="space-y-6">
    <!-- Top Selector Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Meja Kerja Verifikator & Tim Dinas
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Pemeriksaan & Skoring</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                {{ $currentBalai?->nama_balai }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pemeriksaan bukti fisik, skoring reaktif 5 parameter ($P_1\text{--}P_5$), dan aktivasi peringatan dini.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="balaiPick" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Pilih Balai:</label>
                <select id="balaiPick" wire:model.live="selectedBalaiId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    @foreach($balaiList as $b)
                        <option value="{{ $b->id }}" class="dark:bg-gray-800">{{ $b->nama_balai }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="periodePick" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Periode:</label>
                <select id="periodePick" wire:model.live="selectedPeriodeId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" class="dark:bg-gray-800">{{ $p->tahun }} - {{ $p->tahap }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Notification Alert -->
    @if($notificationMessage)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="size-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $notificationMessage }}</span>
            </div>
            <button wire:click="$set('notificationMessage', null)" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    <!-- Split Screen Assessment Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Panel Kiri (Col-span 7): Pratinjau Angka & Berkas Checklist -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Ringkasan Angka Laporan Balai -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
                    Ringkasan Pelaporan Anggaran
                </h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Rencana / Pagu</span>
                        <div class="text-base font-bold font-mono text-gray-900 dark:text-white mt-1 tabular-nums">
                            Rp {{ number_format((float) ($laporan?->rencana_anggaran ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Realisasi Disampaikan</span>
                        <div class="text-base font-bold font-mono text-brand-600 dark:text-brand-400 mt-1 tabular-nums">
                            Rp {{ number_format((float) ($laporan?->realisasi_anggaran ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Persentase Serapan</span>
                        <div class="text-base font-extrabold font-mono {{ ($laporan?->persentase_serapan ?? 0) >= 50 ? 'text-emerald-600' : 'text-amber-600' }} mt-1 tabular-nums">
                            {{ $laporan?->persentase_serapan ?? 0 }}%
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500">
                    <div>
                        Submit Pertama:
                        <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                            {{ $laporan?->tgl_submit_pertama ? $laporan->tgl_submit_pertama->format('d/m/Y H:i') : 'Belum Submit' }}
                        </span>
                    </div>
                    <div>
                        Tenggat SOP:
                        <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">
                            {{ $currentPeriode?->tgl_tenggat_lapor ? $currentPeriode->tgl_tenggat_lapor->format('d/m/Y H:i') : '-' }}
                        </span>
                    </div>
                    <div>
                        Status:
                        <span class="font-semibold text-brand-600">
                            {{ $laporan?->status_laporan?->label() ?? 'Draft' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pemeriksaan Dokumen Wajib -->
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Validasi Checklist Berkas Pendukung
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Klik status untuk mengubah hasil verifikasi dokumen. Skor P2 akan terhitung otomatis.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($laporan?->dokumenLaporan ?? collect() as $dok)
                        <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">
                                    {{ $dok->nama_dokumen_wajib }}
                                </h4>
                                <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                    @if($dok->file_name)
                                        <span class="text-brand-600 dark:text-brand-400 underline cursor-pointer">
                                            {{ $dok->file_name }}
                                        </span>
                                        <span>({{ round($dok->file_size / 1024, 0) }} KB)</span>
                                    @else
                                        <span class="text-amber-500 italic">Belum ada file terunggah</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Fast Toggle Buttons for Admin -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" wire:click="updateDokumenStatus({{ $dok->id }}, 'lengkap')"
                                        class="px-2.5 py-1 rounded text-xs font-semibold transition {{ $dok->status_validasi === 'lengkap' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-emerald-50 hover:text-emerald-700 dark:bg-gray-800 dark:text-gray-400' }}">
                                    Lengkap
                                </button>
                                <button type="button" wire:click="updateDokumenStatus({{ $dok->id }}, 'kurang')"
                                        class="px-2.5 py-1 rounded text-xs font-semibold transition {{ $dok->status_validasi === 'kurang' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-amber-50 hover:text-amber-700 dark:bg-gray-800 dark:text-gray-400' }}">
                                    Kurang
                                </button>
                                <button type="button" wire:click="updateDokumenStatus({{ $dok->id }}, 'tidak_valid')"
                                        class="px-2.5 py-1 rounded text-xs font-semibold transition {{ $dok->status_validasi === 'tidak_valid' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-rose-50 hover:text-rose-700 dark:bg-gray-800 dark:text-gray-400' }}">
                                    Tidak Valid
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-gray-400">
                            Tidak ada berkas yang perlu diverifikasi pada Balai ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Panel Kanan (Col-span 5): Matriks Skoring 5 Parameter ($P_1\text{--}P_5$) & EWS Drawer -->
        <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-20">
            <!-- Live Score Summary Card -->
            <div class="rounded-2xl border p-5 transition-colors shadow-theme-xs {{ $kategoriLive === \App\Enums\RiskCategory::Rendah ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-500/30 dark:bg-emerald-500/10' : ($kategoriLive === \App\Enums\RiskCategory::Sedang ? 'border-amber-200 bg-amber-50/50 dark:border-amber-500/30 dark:bg-amber-500/10' : 'border-rose-200 bg-rose-50/50 dark:border-rose-500/30 dark:bg-rose-500/10') }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                        Kalkulasi Reaktif Livewire 4
                    </span>
                    <x-risk-badge :category="$kategoriLive" :score="$totalSkorLive" />
                </div>

                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold font-mono text-gray-900 dark:text-white tabular-nums">
                        {{ $totalSkorLive }}
                    </span>
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">/ 15 Total Skor</span>
                </div>

                <p class="text-xs text-gray-700 dark:text-gray-300 mt-2 leading-relaxed">
                    {{ $rekomendasiLive }}
                </p>
            </div>

            <!-- Matriks Radio Button 5 Parameter ($P_1 - P_5) -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white pb-2 border-b border-gray-100 dark:border-gray-800">
                    Penilaian 5 Parameter Risiko ($P_1\text{--}P_5$)
                </h3>

                <!-- P1: Ketepatan Waktu -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">P1. Ketepatan Waktu Pelaporan</span>
                        <span class="font-mono font-bold text-brand-600">Skor: {{ $skorP1 }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([0 => 'Tepat (0)', 1 => 'Ringan (1)', 2 => 'Terlambat (2)', 3 => 'Lewat (3)'] as $val => $lbl)
                            <button type="button" wire:click="$set('skorP1', {{ $val }})"
                                    class="py-1.5 px-2 rounded-lg text-xs font-semibold border transition text-center {{ $skorP1 === $val ? 'bg-brand-500 text-white border-brand-500' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- P2: Kelengkapan Dokumen -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">P2. Kelengkapan Berkas Dokumen</span>
                        <span class="font-mono font-bold text-brand-600">Skor: {{ $skorP2 }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([0 => 'Lengkap (0)', 1 => 'Minor (1)', 2 => 'Kurang (2)', 3 => 'Tidak Ada (3)'] as $val => $lbl)
                            <button type="button" wire:click="$set('skorP2', {{ $val }})"
                                    class="py-1.5 px-2 rounded-lg text-xs font-semibold border transition text-center {{ $skorP2 === $val ? 'bg-brand-500 text-white border-brand-500' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- P3: Akurasi & Konsistensi Data -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">P3. Akurasi & Konsistensi Angka</span>
                        <span class="font-mono font-bold text-brand-600">Skor: {{ $skorP3 }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([0 => 'Akurat (0)', 1 => 'Selisih Kecil (1)', 2 => 'Perlu Cek (2)', 3 => 'Material (3)'] as $val => $lbl)
                            <button type="button" wire:click="$set('skorP3', {{ $val }})"
                                    class="py-1.5 px-2 rounded-lg text-xs font-semibold border transition text-center {{ $skorP3 === $val ? 'bg-brand-500 text-white border-brand-500' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- P4: Kesesuaian Realisasi vs Rencana -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">P4. Kesesuaian Realisasi vs Rencana</span>
                        <span class="font-mono font-bold text-brand-600">Skor: {{ $skorP4 }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([0 => 'Sesuai (0)', 1 => 'Deviasi Kecil (1)', 2 => 'Deviasi Sedang (2)', 3 => 'Deviasi Berat (3)'] as $val => $lbl)
                            <button type="button" wire:click="$set('skorP4', {{ $val }})"
                                    class="py-1.5 px-2 rounded-lg text-xs font-semibold border transition text-center {{ $skorP4 === $val ? 'bg-brand-500 text-white border-brand-500' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- P5: Tindak Lanjut Monitoring -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">P5. Tindak Lanjut Hasil Monitoring</span>
                        <span class="font-mono font-bold text-brand-600">Skor: {{ $skorP5 }}</span>
                    </div>
                    <div class="grid grid-cols-4 gap-1.5">
                        @foreach([0 => 'Tuntas (0)', 1 => 'Tertunda Minor (1)', 2 => 'Perlu Diingatkan (2)', 3 => 'Belum Tindak (3)'] as $val => $lbl)
                            <button type="button" wire:click="$set('skorP5', {{ $val }})"
                                    class="py-1.5 px-2 rounded-lg text-xs font-semibold border transition text-center {{ $skorP5 === $val ? 'bg-brand-500 text-white border-brand-500' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                {{ $lbl }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Form Pemicu EWS & Tiket Perbaikan (Terbuka Otomatis jika Skor >= 5) -->
                <div x-data="{ openEws: {{ $totalSkorLive >= 5 ? 'true' : 'false' }} }" class="pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="openEws = !openEws" class="flex items-center justify-between w-full text-xs font-bold text-amber-600 dark:text-amber-400">
                        <span>Pemicu Early Warning System (EWS) & Instruksi Perbaikan</span>
                        <span x-text="openEws ? '▲' : '▼'"></span>
                    </button>

                    <div x-show="openEws" class="mt-3 space-y-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Indikator Pemicu EWS
                            </label>
                            <select wire:model="selectedEwsCode" class="w-full p-2 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="EWS_1">EWS 1: Ketepatan Waktu Pelaporan</option>
                                <option value="EWS_2">EWS 2: Kelengkapan & Validitas Data</option>
                                <option value="EWS_3">EWS 3: Tindak Lanjut Monitoring</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Instruksi Perbaikan Khusus
                            </label>
                            <textarea wire:model="instruksiPerbaikan" rows="2"
                                      placeholder="Tuliskan catatan kekurangan yang harus diperbaiki oleh Operator Balai..."
                                      class="w-full p-2 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    PIC Balai
                                </label>
                                <input type="text" wire:model="penanggungJawab" class="w-full p-2 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Batas Koreksi (Hari)
                                </label>
                                <input type="number" min="1" max="14" wire:model="hariBatasKoreksi" class="w-full p-2 text-xs rounded-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Button with data-loading -->
                <button type="button" wire:click="saveAssessment"
                        class="w-full py-3 px-4 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-theme-xs flex items-center justify-center gap-2 transition data-loading:opacity-60 data-loading:pointer-events-none mt-2">
                    <span>Simpan Penilaian & Verifikasi</span>
                    <svg class="hidden data-loading:inline-block size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>