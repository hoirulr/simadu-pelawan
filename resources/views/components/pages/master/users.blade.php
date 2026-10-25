<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\BalaiPenyuluh;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Master Akun Pengguna')] class extends Component
{
    public string $search = '';
    public string $filterRole = '';
    public bool $showModal = false;
    public ?int $editingUserId = null;

    public string $name = '';
    public string $email = '';
    public string $role = 'operator_balai';
    public ?int $balai_id = 1;
    public string $password = '';
    public string $password_confirmation = '';

    public ?string $feedbackMessage = null;

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingUserId = null;
        $this->name = '';
        $this->email = '';
        $this->role = 'operator_balai';
        $this->balai_id = 1;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $user = User::findOrFail($id);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->balai_id = $user->balai_id;
        $this->password = '';
        $this->password_confirmation = '';
        $this->showModal = true;
    }

    public function saveUser(): void
    {
        $rules = [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email,'.($this->editingUserId ?: 'NULL').',id',
            'role' => 'required|in:admin,operator_balai',
            'balai_id' => $this->role === 'operator_balai' ? 'required|exists:balai_penyuluh,id' : 'nullable',
        ];

        if (! $this->editingUserId || ! empty($this->password)) {
            $rules['password'] = 'required|min:6|confirmed';
        }

        $this->validate($rules, [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar oleh pengguna lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'balai_id.required' => 'Balai Penyuluh KB wajib dipilih untuk peran Operator Balai.',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => UserRole::from($this->role),
            'balai_id' => $this->role === 'operator_balai' ? $this->balai_id : null,
        ];

        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update($data);
            $this->feedbackMessage = "Data pengguna {$user->name} berhasil diperbarui.";
        } else {
            $user = User::create($data);
            $this->feedbackMessage = "Akun pengguna baru {$user->name} berhasil dibuat.";
        }

        $this->showModal = false;
    }

    public function deleteUser(int $id): void
    {
        if (Auth::id() === $id) {
            $this->feedbackMessage = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.';
            return;
        }

        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        $this->feedbackMessage = "Akun pengguna {$name} telah berhasil dihapus.";
    }

    public function with(): array
    {
        $query = User::with('balaiPenyuluh');

        if (! empty($this->filterRole)) {
            $query->where('role', $this->filterRole);
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $users = $query->orderBy('role', 'asc')->orderBy('id', 'asc')->get();
        $balaiList = BalaiPenyuluh::orderBy('id', 'asc')->get();

        return [
            'users' => $users,
            'balaiList' => $balaiList,
            'totalUsers' => User::count(),
            'totalAdmin' => User::where('role', UserRole::Admin)->count(),
            'totalOperator' => User::where('role', UserRole::OperatorBalai)->count(),
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
                    Master Pengaturan Sistem
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">RBAC 2 Peran Utama</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Manajemen Akun Pengguna (Admin & Operator Balai)
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola hak akses Admin Dinas DP2KBP3A dan 8 akun Operator Balai Penyuluh KB Kabupaten Bangka.
            </p>
        </div>

        <button type="button" wire:click="openCreateModal"
                class="px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-theme-xs flex items-center gap-2 transition shrink-0">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>Tambah Akun Baru</span>
        </button>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total Akun Terdaftar</span>
                <div class="text-2xl font-extrabold font-mono text-gray-900 dark:text-white mt-1">{{ $totalUsers }}</div>
            </div>
            <div class="size-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Admin Dinas DP2KBP3A</span>
                <div class="text-2xl font-extrabold font-mono text-purple-600 dark:text-purple-400 mt-1">{{ $totalAdmin }}</div>
            </div>
            <div class="size-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 flex items-center justify-center text-purple-600 dark:text-purple-400">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>

        <div class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex items-center justify-between">
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Operator 8 Balai KB</span>
                <div class="text-2xl font-extrabold font-mono text-brand-600 dark:text-brand-400 mt-1">{{ $totalOperator }}</div>
            </div>
            <div class="size-10 rounded-xl bg-brand-50 dark:bg-brand-950/40 flex items-center justify-center text-brand-600 dark:text-brand-400">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Feedback Message -->
    @if($feedbackMessage)
        <div class="p-4 rounded-xl bg-brand-50 border border-brand-200 text-brand-800 dark:bg-brand-500/10 dark:border-brand-500/30 dark:text-brand-300 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="size-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-brand-500 hover:text-brand-700">&times;</button>
        </div>
    @endif

    <!-- Search & Filter Controls -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:max-w-xs">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email pengguna..."
                       class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-xs text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <svg class="size-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" wire:click="$set('filterRole', '')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterRole === '' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    Semua Role
                </button>
                <button type="button" wire:click="$set('filterRole', 'admin')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterRole === 'admin' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    Admin Dinas
                </button>
                <button type="button" wire:click="$set('filterRole', 'operator_balai')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $filterRole === 'operator_balai' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}">
                    Operator Balai
                </button>
            </div>
        </div>

        <!-- Table of Users -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4">Hak Akses / Peran</th>
                        <th class="py-3.5 px-4">Wilayah / Balai KB</th>
                        <th class="py-3.5 px-4 text-center">Simulasi / Switch</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($users as $index => $u)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition {{ Auth::id() === $u->id ? 'bg-brand-50/20 dark:bg-brand-500/5' : '' }}">
                            <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-9 rounded-full bg-gradient-to-tr {{ $u->role === UserRole::Admin ? 'from-purple-500 to-indigo-500' : 'from-brand-500 to-emerald-500' }} text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-sm">
                                        {{ $u->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $u->name }}</span>
                                            @if(Auth::id() === $u->id)
                                                <span class="px-1.5 py-0.2 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    Anda
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-400 font-mono">{{ $u->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($u->role === UserRole::Admin)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800">
                                        Admin Dinas
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-200 dark:bg-brand-900/30 dark:text-brand-300 dark:border-brand-800">
                                        Operator Balai
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                @if($u->balaiPenyuluh)
                                    <div class="font-bold text-gray-800 dark:text-gray-200">
                                        {{ $u->balaiPenyuluh->nama_balai }}
                                    </div>
                                    <div class="text-gray-400 text-[11px]">
                                        Kecamatan {{ $u->balaiPenyuluh->kecamatan }}
                                    </div>
                                @else
                                    <span class="text-gray-500 italic">Seluruh 8 Balai (Dinas Kabupaten)</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if(Auth::id() !== $u->id)
                                    <a href="/switch-user/{{ $u->id }}"
                                       class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-gray-100 hover:bg-brand-50 hover:text-brand-600 text-gray-700 dark:bg-gray-800 dark:text-gray-300 transition">
                                        Masuk Sebagai &rarr;
                                    </a>
                                @else
                                    <span class="text-[11px] font-mono text-emerald-600 font-bold">Sedang Aktif</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" wire:click="openEditModal({{ $u->id }})"
                                            class="p-1.5 text-gray-500 hover:text-brand-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                            title="Ubah Data Pengguna">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if(Auth::id() !== $u->id)
                                        <button type="button" wire:click="deleteUser({{ $u->id }})"
                                                wire:confirm="Yakin ingin menghapus akun pengguna ini?"
                                                class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition"
                                                title="Hapus Akun">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400 text-xs">
                                Tidak ada data pengguna yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah/Ubah Akun -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="relative w-full max-w-[480px] rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $editingUserId ? 'Ubah Data Pengguna' : 'Tambah Pengguna Baru' }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Konfigurasi akun dan hak akses SIMADU PELAWAN.
                        </p>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-light">
                        &times;
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                        <input type="text" wire:model="name" placeholder="Contoh: Operator Balai Sungailiat"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Alamat Email</label>
                        <input type="email" wire:model="email" placeholder="nama@simadupelawan.go.id"
                               class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                        @error('email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Peran Pengguna (Role)</label>
                        <select wire:model.live="role" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="operator_balai">Operator Balai Penyuluh KB (Cakupan 1 Balai)</option>
                            <option value="admin">Admin Dinas DP2KBP3A (Cakupan Penuh Kabupaten)</option>
                        </select>
                        @error('role') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    @if($role === 'operator_balai')
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Balai Penyuluh KB Terikat</label>
                            <select wire:model="balai_id" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                @foreach($balaiList as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama_balai }} (Kec. {{ $b->kecamatan }})</option>
                                @endforeach
                            </select>
                            @error('balai_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                {{ $editingUserId ? 'Password Baru (Kosongkan jika tak diubah)' : 'Password' }}
                            </label>
                            <input type="password" wire:model="password" placeholder="Minimal 6 karakter"
                                   class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                            @error('password') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Konfirmasi Password</label>
                            <input type="password" wire:model="password_confirmation" placeholder="Ulangi password"
                                   class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2 bg-gray-50 dark:bg-gray-800/40">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="saveUser" class="px-4 py-2 rounded-xl text-xs font-semibold bg-brand-500 hover:bg-brand-600 text-white shadow-theme-xs transition">
                        Simpan Akun
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
