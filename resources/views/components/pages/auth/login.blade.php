<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.guest')] #[Title('Masuk ke Sistem')] class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public ?string $errorMessage = null;

    public function mount(): mixed
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin();
        }

        return null;
    }

    public function fillCredentials(string $email, string $password = 'password'): void
    {
        $this->resetValidation();
        $this->errorMessage = null;
        $this->email = $email;
        $this->password = $password;
    }

    public function quickLogin(string $email, string $password = 'password'): mixed
    {
        if (app()->isProduction()) {
            abort(403, 'Akses masuk demonstrasi dinonaktifkan pada lingkungan produksi.');
        }

        $this->fillCredentials($email, $password);

        return $this->authenticate();
    }

    public function authenticate(): mixed
    {
        $this->errorMessage = null;

        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->errorMessage = 'Terlalu banyak percobaan masuk yang gagal. Silakan coba lagi dalam '.$seconds.' detik.';

            return null;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            return $this->redirectAfterLogin();
        }

        RateLimiter::hit($throttleKey, 60);

        $this->errorMessage = 'Alamat email atau kata sandi yang Anda masukkan salah. Silakan coba lagi.';

        return null;
    }

    protected function redirectAfterLogin(): mixed
    {
        $user = Auth::user();
        if ($user && $user->role === UserRole::OperatorBalai) {
            return redirect()->intended('/balai/laporan');
        }

        return redirect()->intended('/dashboard');
    }
};
?>

<div class="min-h-screen flex flex-col justify-between relative overflow-hidden bg-gradient-to-br from-slate-50 via-gray-50 to-brand-50/30 dark:from-gray-950 dark:via-gray-900 dark:to-brand-950/20">
    <!-- Background Glows -->
    <div class="absolute top-0 -left-40 size-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 -right-40 size-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Top Navigation Bar -->
    <header class="w-full max-w-7xl mx-auto px-6 py-5 flex items-center justify-between z-10">
        <div class="flex items-center gap-3">
            <div class="size-11 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/25 shrink-0">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <span class="block text-lg font-black tracking-tight text-gray-900 dark:text-white leading-none">
                    SI MADU <span class="text-brand-600 dark:text-brand-400">PELAWAN</span>
                </span>
                <span class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 tracking-wider">
                    DP2KBP3A KABUPATEN BANGKA
                </span>
            </div>
        </div>

        <!-- Dark Mode Toggle Button -->
        <button type="button"
                onclick="
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-color-scheme', isDark ? 'dark' : 'light');
                "
                class="size-10 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-theme-xs transition"
                title="Ganti Tema Gelap / Terang">
            <svg class="size-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg class="size-5 block dark:hidden text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
    </header>

    <!-- Main Content Area -->
    <main class="w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 z-10 flex-1 flex items-center justify-center">
        <div class="w-full max-w-[480px]">
            <!-- Card Container -->
            <div class="rounded-3xl border border-gray-200 bg-white/95 dark:border-gray-800 dark:bg-gray-900/95 backdrop-blur-xl p-8 sm:p-9 shadow-theme-xl">
                <!-- Title & Description -->
                <div class="text-center mb-7">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 border border-brand-100 dark:bg-brand-950/40 dark:text-brand-300 dark:border-brand-900/50 mb-3">
                        <span class="size-1.5 rounded-full bg-brand-500"></span>
                        Portal Akses Resmi
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        Masuk ke Sistem
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                        Sistem Informasi Monitoring, Dukungan, dan Pengawasan Pelaporan Anggaran Balai KB
                    </p>
                </div>

                <!-- Error Alert Notice -->
                @if($errorMessage)
                    <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-300 flex items-start gap-3 text-xs leading-relaxed animate-fade-in">
                        <svg class="size-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <span class="font-bold block">Autentikasi Gagal</span>
                            <span>{{ $errorMessage }}</span>
                        </div>
                    </div>
                @endif

                <!-- Form Login -->
                <form wire:submit.prevent="authenticate" class="space-y-4">
                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Alamat Email Pengguna
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </span>
                            <input id="email" type="email" wire:model="email" autofocus
                                   placeholder="username"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white transition">
                        </div>
                        @error('email') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Password Input with Alpine Show/Hide -->
                    <div x-data="{ show: false }">
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Kata Sandi
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input id="password" :type="show ? 'text' : 'password'" wire:model="password"
                                   placeholder="password"
                                   class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white transition font-mono">
                            <button type="button" @click="show = !show"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg x-show="!show" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" x-cloak class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        @error('password') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="remember"
                                   class="size-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                            <span class="text-xs text-gray-600 dark:text-gray-400 select-none">Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Submit Button with Livewire 4 data-loading -->
                    <div class="pt-2">
                        <button type="submit"
                                class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-700 hover:to-indigo-700 text-white font-semibold text-sm shadow-lg shadow-brand-500/25 transition flex items-center justify-center gap-2 data-loading:opacity-60 data-loading:pointer-events-none">
                            <span>Masuk ke Sistem</span>
                            <svg class="size-4 hidden data-loading:inline-block animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </button>
                    </div>
                </form>

                @if(!app()->isProduction())
                <!-- Quick Demo Login Presets -->
                <div class="mt-7 pt-6 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] uppercase tracking-wider font-bold text-gray-400">
                            Akun Demonstrasi / Pengujian:
                        </span>
                        <span class="text-[10px] text-brand-600 dark:text-brand-400 font-mono font-semibold">
                            Klik untuk masuk instan
                        </span>
                    </div>

                    <div class="space-y-2">
                        <!-- Admin Dinas -->
                        <button type="button" wire:click="quickLogin('admin@simadupelawan.go.id', 'password')"
                                class="w-full p-2.5 rounded-xl border border-purple-200 bg-purple-50/50 hover:bg-purple-100/70 dark:border-purple-900/40 dark:bg-purple-950/20 dark:hover:bg-purple-950/40 text-left transition flex items-center justify-between group">
                            <div class="flex items-center gap-2.5">
                                <span class="size-7 rounded-lg bg-purple-600 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                                    AD
                                </span>
                                <div>
                                    <span class="block text-xs font-bold text-purple-900 dark:text-purple-200">
                                        Admin Dinas DP2KBP3A
                                    </span>
                                    <span class="block text-[11px] text-purple-700/80 dark:text-purple-400 font-mono">
                                        admin@simadupelawan.go.id
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-purple-600 group-hover:translate-x-0.5 transition">&rarr;</span>
                        </button>

                        <!-- Operator Balai Sungailiat -->
                        <button type="button" wire:click="quickLogin('operator.sungailiat@simadupelawan.go.id', 'password')"
                                class="w-full p-2.5 rounded-xl border border-brand-200 bg-brand-50/50 hover:bg-brand-100/70 dark:border-brand-900/40 dark:bg-brand-950/20 dark:hover:bg-brand-950/40 text-left transition flex items-center justify-between group">
                            <div class="flex items-center gap-2.5">
                                <span class="size-7 rounded-lg bg-brand-600 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                                    KB
                                </span>
                                <div>
                                    <span class="block text-xs font-bold text-brand-900 dark:text-brand-200">
                                        Operator Balai KB Sungailiat
                                    </span>
                                    <span class="block text-[11px] text-brand-700/80 dark:text-brand-400 font-mono">
                                        operator.sungailiat@simadupelawan.go.id
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-brand-600 group-hover:translate-x-0.5 transition">&rarr;</span>
                        </button>

                        <!-- Operator Balai Merawang -->
                        <button type="button" wire:click="quickLogin('operator.merawang@simadupelawan.go.id', 'password')"
                                class="w-full p-2.5 rounded-xl border border-gray-200 bg-gray-50/70 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:bg-gray-800/80 text-left transition flex items-center justify-between group">
                            <div class="flex items-center gap-2.5">
                                <span class="size-7 rounded-lg bg-gray-600 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                                    KB
                                </span>
                                <div>
                                    <span class="block text-xs font-bold text-gray-800 dark:text-gray-200">
                                        Operator Balai KB Merawang
                                    </span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400 font-mono">
                                        operator.merawang@simadupelawan.go.id
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 group-hover:translate-x-0.5 transition">&rarr;</span>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-6 py-5 text-center text-xs text-gray-500 dark:text-gray-500 z-10 space-y-1">
        <p class="font-medium text-gray-700 dark:text-gray-400">
            &ldquo;Laporan Tepat Waktu, Data Lengkap, Tata Kelola Lebih Baik.&rdquo;
        </p>
        <p>
            &copy; {{ date('Y') }} Dinas Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan dan Perlindungan Anak (DP2KBP3A) Kabupaten Bangka.
        </p>
    </footer>
</div>
