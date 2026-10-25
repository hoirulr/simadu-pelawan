<?php

declare(strict_types=1);

use App\Enums\EwsStatus;
use App\Models\BalaiPenyuluh;
use App\Models\EwsAlert;
use App\Models\PeriodeAnggaran;
use App\Models\TindakLanjutMonitoring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Tiket Dukungan & Tindak Lanjut')] class extends Component
{
    public int $selectedPeriodeId = 1;
    public ?int $selectedBalaiId = null;
    public string $filterStatus = '';

    // Modal Response for Operator Balai
    public bool $showResponseModal = false;
    public ?int $selectedTicketId = null;
    public string $catatanBalai = '';
    public string $fileBuktiKoreksi = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->balai_id) {
            $this->selectedBalaiId = $user->balai_id;
        } elseif (request()->has('balai_id')) {
            $this->selectedBalaiId = (int) request('balai_id');
        }

        $active = PeriodeAnggaran::where('is_active', true)->first();
        if ($active) {
            $this->selectedPeriodeId = $active->id;
        }
    }

    public function openResponseModal(int $ticketId): void
    {
        $this->resetValidation();
        $ticket = TindakLanjutMonitoring::findOrFail($ticketId);
        $this->selectedTicketId = $ticket->id;
        $this->catatanBalai = $ticket->catatan_balai ?? '';
        $this->fileBuktiKoreksi = $ticket->file_bukti_koreksi ?? '';
        $this->showResponseModal = true;
    }

    public function submitBalaiResponse(): void
    {
        $this->validate([
            'catatanBalai' => 'required|string|min:5|max:1000',
            'fileBuktiKoreksi' => 'nullable|string|max:255',
        ], [
            'catatanBalai.required' => 'Penjelasan/catatan tindak lanjut balai wajib diisi.',
            'catatanBalai.min' => 'Catatan minimal 5 karakter.',
        ]);

        if ($this->selectedTicketId) {
            $ticket = TindakLanjutMonitoring::with('laporanAnggaran')->findOrFail($this->selectedTicketId);
            $user = Auth::user();
            if ($user && $user->isOperatorBalai() && $ticket->laporanAnggaran->balai_id !== $user->balai_id) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menindaklanjuti tiket balai lain.');
            }

            $ticket->update([
                'catatan_balai' => $this->catatanBalai,
                'file_bukti_koreksi' => $this->fileBuktiKoreksi ?: 'berkas_koreksi_tindak_lanjut.pdf',
                'status' => 'In Progress',
            ]);

            $this->feedbackMessage = 'Penjelasan dan bukti tindak lanjut berhasil dikirim ke Admin Dinas (Status: In Progress).';
            $this->showResponseModal = false;
        }
    }

    public function verifyTicket(int $ticketId): void
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Akses Ditolak: Hanya Admin yang berwenang memverifikasi tiket perbaikan.');
        }

        $ticket = TindakLanjutMonitoring::with('ewsAlert')->find($ticketId);
        if ($ticket) {
            $ticket->update([
                'status' => 'Verified',
                'waktu_selesai_verifikasi' => Carbon::now(),
                'verified_by' => Auth::id() ?? 1,
            ]);

            // Jika tiket terikat ke EWS Alert, tandai Resolved
            if ($ticket->ewsAlert) {
                $ticket->ewsAlert->update(['status_ews' => EwsStatus::Resolved]);
            }

            $this->feedbackMessage = 'Tiket perbaikan telah berhasil diverifikasi Tuntas (Resolved).';
        }
    }

    public function with(): array
    {
        $periodes = PeriodeAnggaran::orderBy('tahun', 'desc')->get();
        $balaiList = BalaiPenyuluh::orderBy('id', 'asc')->get();

        $query = TindakLanjutMonitoring::whereHas('laporanAnggaran', function ($q) {
            $q->where('periode_id', $this->selectedPeriodeId);
            if ($this->selectedBalaiId) {
                $q->where('balai_id', $this->selectedBalaiId);
            }
        })->with(['laporanAnggaran.balaiPenyuluh', 'ewsAlert', 'verifikatorUser']);

        if (! empty($this->filterStatus)) {
            $query->where('status', $this->filterStatus);
        }

        $tickets = $query->orderBy('status', 'asc')->orderBy('id', 'desc')->get();

        $totalTickets = TindakLanjutMonitoring::whereHas('laporanAnggaran', function ($q) {
            $q->where('periode_id', $this->selectedPeriodeId);
        })->count();

        $verifiedTickets = TindakLanjutMonitoring::whereHas('laporanAnggaran', function ($q) {
            $q->where('periode_id', $this->selectedPeriodeId);
        })->where('status', 'Verified')->count();

        $pendingTickets = TindakLanjutMonitoring::whereHas('laporanAnggaran', function ($q) {
            $q->where('periode_id', $this->selectedPeriodeId);
        })->where('status', '!=', 'Verified')->count();

        return [
            'periodes' => $periodes,
            'balaiList' => $balaiList,
            'tickets' => $tickets,
            'totalTickets' => $totalTickets,
            'verifiedTickets' => $verifiedTickets,
            'pendingTickets' => $pendingTickets,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Modul Tindak Lanjut & Dukungan Balai
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">FR-04.1 Dua Arah</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Daftar Tiket Tindak Lanjut & Dukungan Perbaikan
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Ruang komunikasi dua arah antara Tim Dinas dan Balai untuk penyelesaian temuan kekurangan berkas dan deviasi anggaran.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Balai Filter (All / Selected) -->
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="balaiFilterTicket" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Balai:</label>
                <select id="balaiFilterTicket" wire:model.live="selectedBalaiId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    <option value="">Semua 8 Balai</option>
                    @foreach($balaiList as $b)
                        <option value="{{ $b->id }}" class="dark:bg-gray-800">{{ $b->nama_balai }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Periode Filter -->
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="periodeFilterTicket" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Periode:</label>
                <select id="periodeFilterTicket" wire:model.live="selectedPeriodeId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" class="dark:bg-gray-800">{{ $p->tahun }} - {{ $p->tahap }}</option>
                    @endforeach
                </select>
            </div>

            <a href="/nilai-ekonomis" class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 text-xs font-semibold transition">
                Evaluasi Nilai Ekonomis &rarr;
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Tiket Periode Ini</span>
            <div class="text-2xl font-black font-mono text-gray-900 dark:text-white mt-1">{{ $totalTickets }}</div>
            <p class="text-[11px] text-gray-400 mt-1">Akumulasi catatan kekurangan</p>
        </div>

        <div class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Perlu Tindak Lanjut / Proses</span>
            <div class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1">{{ $pendingTickets }}</div>
            <p class="text-[11px] text-gray-400 mt-1">Status Pending / In Progress</p>
        </div>

        <div class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Tuntas Diverifikasi</span>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $verifiedTickets }}</div>
            <p class="text-[11px] text-gray-400 mt-1">EWS Alert ditutup (Resolved)</p>
        </div>
    </div>

    <!-- Feedback Notice -->
    @if($feedbackMessage)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="size-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    <!-- Status Filter Buttons & Table -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="$set('filterStatus', '')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterStatus === '' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    Semua Status
                </button>
                <button type="button" wire:click="$set('filterStatus', 'Pending')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterStatus === 'Pending' ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    Pending
                </button>
                <button type="button" wire:click="$set('filterStatus', 'In Progress')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterStatus === 'In Progress' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    In Progress
                </button>
                <button type="button" wire:click="$set('filterStatus', 'Verified')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterStatus === 'Verified' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    Verified (Tuntas)
                </button>
            </div>

            <span class="text-xs font-mono text-gray-400">
                Menampilkan {{ $tickets->count() }} tiket
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Balai Penyuluh KB</th>
                        <th class="py-3 px-4">Instruksi & Peringatan EWS</th>
                        <th class="py-3 px-4">Respon / Bukti Balai</th>
                        <th class="py-3 px-4">Tenggat Koreksi</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($tickets as $index => $ticket)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                            <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ $ticket->laporanAnggaran->balaiPenyuluh->nama_balai }}
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    Kec. {{ $ticket->laporanAnggaran->balaiPenyuluh->kecamatan }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 max-w-sm">
                                @if($ticket->ewsAlert)
                                    <div class="mb-1">
                                        <span class="inline-block px-1.5 py-0.5 text-[10px] font-mono font-bold rounded {{ $ticket->ewsAlert->status_ews === EwsStatus::Escalated ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $ticket->ewsAlert->kode_ews->value }}
                                        </span>
                                        <span class="text-[11px] text-gray-500 font-semibold ml-1">
                                            {{ $ticket->ewsAlert->kondisi_pemicu }}
                                        </span>
                                    </div>
                                @endif
                                <p class="text-xs font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $ticket->instruksi_perbaikan }}
                                </p>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs text-xs">
                                @if($ticket->catatan_balai)
                                    <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                                        <p class="text-gray-800 dark:text-gray-200">
                                            "{{ $ticket->catatan_balai }}"
                                        </p>
                                        @if($ticket->file_bukti_koreksi)
                                            <div class="mt-1 flex items-center gap-1 text-[11px] text-brand-600 dark:text-brand-400 font-mono">
                                                <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                                <span>{{ $ticket->file_bukti_koreksi }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 italic text-xs">Belum ada tanggapan/bukti dari Balai</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs font-mono">
                                @if($ticket->ewsAlert?->batas_waktu_koreksi)
                                    <span class="{{ Carbon::now()->isAfter($ticket->ewsAlert->batas_waktu_koreksi) && $ticket->status !== 'Verified' ? 'text-rose-600 font-bold' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $ticket->ewsAlert->batas_waktu_koreksi->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ticket->status === 'Verified' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400' : ($ticket->status === 'In Progress' ? 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400' : 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300') }}">
                                    {{ $ticket->status }}
                                </span>
                                @if($ticket->waktu_selesai_verifikasi)
                                    <div class="text-[10px] text-gray-400 font-mono mt-0.5">
                                        Tuntas: {{ $ticket->waktu_selesai_verifikasi->format('d/m H:i') }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Balai Response Button -->
                                    <button type="button" wire:click="openResponseModal({{ $ticket->id }})"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-brand-50 hover:text-brand-600 text-gray-700 dark:bg-gray-800 dark:text-gray-300 transition"
                                            title="Tanggapi / Kirim Bukti Balai">
                                        Tanggapi
                                    </button>

                                    <!-- Admin Verify Button -->
                                    @if(auth()->user()?->isAdmin())
                                        @if($ticket->status !== 'Verified')
                                            <button type="button" wire:click="verifyTicket({{ $ticket->id }})"
                                                    class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition"
                                                    title="Tandai Selesai (Verified)">
                                                Verifikasi
                                            </button>
                                        @else
                                            <span class="text-xs text-emerald-600 font-semibold flex items-center justify-center gap-0.5" title="Terverifikasi Tuntas">
                                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400 text-xs">
                                Tidak ada tiket perbaikan pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Respon & Bukti Balai -->
    @if($showResponseModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="relative w-full max-w-[480px] rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Kirim Tindak Lanjut & Bukti Koreksi Balai
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Isi penjelasan klarifikasi deviasi atau konfirmasi perbaikan dokumen.
                        </p>
                    </div>
                    <button wire:click="$set('showResponseModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-light">
                        &times;
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Uraian Penjelasan / Klarifikasi Balai KB
                        </label>
                        <textarea wire:model="catatanBalai" rows="4" placeholder="Jelaskan tindakan yang telah diambil atau klarifikasi terkait dokumen anggaran..."
                                  class="w-full p-3 rounded-xl border border-gray-200 text-xs text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('catatanBalai') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Nama Berkas Bukti Koreksi / Lampiran
                        </label>
                        <input type="text" wire:model="fileBuktiKoreksi" placeholder="Contoh: Bukti_SPJ_Revisi_Kwitansi.pdf"
                               class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2 bg-gray-50 dark:bg-gray-800/40">
                    <button type="button" wire:click="$set('showResponseModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="submitBalaiResponse" class="px-4 py-2 rounded-xl text-xs font-semibold bg-brand-500 hover:bg-brand-600 text-white shadow-theme-xs transition">
                        Kirim Tindak Lanjut
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>