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

    public function fillCredentials(string $email, string $password = 'Bangka*1901*'): void
    {
        $this->resetValidation();
        $this->errorMessage = null;
        $this->email = $email;
        $this->password = $password;
    }

    public function quickLogin(string $email, string $password = 'Bangka*1901*'): mixed
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

<div class="min-h-screen flex flex-col justify-between relative overflow-hidden bg-gradient-to-br from-[#ECE8FD] via-[#F6F5FD] to-[#EDE9F8] dark:from-gray-950 dark:via-gray-900 dark:to-purple-950/20">

    <!-- Soft Ambient Glows -->
    <div class="absolute -top-32 -left-32 size-[420px] bg-purple-400/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 size-[420px] bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Dual Office Building Background Layer (1793871007923.jpg on left & 1793871007918.jpg on right with KB monument) -->
    <div class="absolute inset-0 bg-cover bg-center bg-no-repeat pointer-events-none opacity-45 dark:opacity-15 z-0" style="background-image: url('{{ asset('images/auth/bg-office-composite.jpg') }}');"></div>

    <!-- Bottom Wave Background Layer: Right Courtyard with KB & Gold Wave -->
    <div class="absolute bottom-0 right-0 w-[55%] max-w-[700px] h-48 sm:h-64 pointer-events-none z-0 select-none">
        <img src="{{ asset('images/auth/waves-bottom-right.png') }}" alt="" class="w-full h-full object-cover object-right-bottom opacity-95 dark:opacity-30" />
    </div>

    <!-- Bottom Wave Background Layer: Left Jasmine Bouquet & Purple Wave -->
    <div class="absolute bottom-0 left-0 w-[44%] max-w-[440px] h-52 sm:h-72 pointer-events-none z-0 select-none">
        <img src="{{ asset('images/auth/melati-corner.png') }}" alt="" class="w-full h-full object-contain object-left-bottom drop-shadow-2xl" />
    </div>

    <!-- Top Navigation Bar -->
    <header class="w-full max-w-7xl mx-auto px-6 sm:px-8 py-4 sm:py-5 flex items-center justify-between z-20 relative">
        <div class="flex items-center gap-3">
            <div class="size-11 sm:size-12 rounded-2xl overflow-hidden shadow-lg shadow-purple-600/20 shrink-0">
                <img src="{{ asset('images/auth/melati-app-logo.png') }}" alt="SI MELATI" class="w-full h-full object-cover" />
            </div>
            <div>
                <span class="block text-xl sm:text-2xl font-black tracking-tight text-[#1E1B4B] dark:text-white leading-none">
                    SI <span class="text-[#5B21B6] dark:text-purple-400">MELATI</span>
                </span>
                <span class="block text-[10px] sm:text-[11px] font-bold text-slate-500 dark:text-slate-400 tracking-wider uppercase mt-1">
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
                class="size-10 sm:size-11 rounded-2xl bg-white/90 dark:bg-gray-800 border border-purple-100/80 dark:border-gray-700 flex items-center justify-center text-slate-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-700 shadow-sm transition"
                title="Ganti Tema Gelap / Terang">
            <svg class="size-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg class="size-5 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
    </header>

    <!-- Main Content Area -->
    <main class="w-full max-w-6xl mx-auto px-4 sm:px-6 py-2 sm:py-4 z-10 relative flex-1 flex items-center justify-center">
        <div class="w-full flex flex-col lg:flex-row items-center justify-center gap-8 lg:gap-14 my-auto">

            <!-- Left / Center: The Login Card -->
            <div class="w-full max-w-[440px] relative">
                <!-- Floral Ornament: Top Right Corner of Card -->
                <div class="absolute -top-7 -right-7 sm:-top-8 sm:-right-8 w-24 sm:w-28 pointer-events-none select-none z-30">
                    <img src="{{ asset('images/auth/melati-card-tr.png') }}" alt="" class="w-full h-auto drop-shadow-md" />
                </div>

                <!-- Floral Ornament: Bottom Left Corner of Card -->
                <div class="absolute -bottom-6 -left-6 sm:-bottom-7 sm:-left-7 w-20 sm:w-24 pointer-events-none select-none z-30">
                    <img src="{{ asset('images/auth/melati-card-bl.png') }}" alt="" class="w-full h-auto drop-shadow-md" />
                </div>

                <!-- The Card Box -->
                <div class="rounded-[28px] sm:rounded-[32px] border border-white/80 dark:border-gray-800 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl p-6 sm:p-8 shadow-[0_20px_50px_-12px_rgba(76,29,149,0.18),0_4px_16px_-4px_rgba(0,0,0,0.06)] relative z-10">

                    <!-- Portal Akses Resmi Badge -->
                    <div class="flex justify-center mb-2.5">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-[#EDE9FE] text-[#5B21B6] border border-[#DDD6FE] dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-800/60 shadow-xs">
                            <svg class="size-3.5 text-[#5B21B6] dark:text-purple-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                            <span>Portal Akses Resmi</span>
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl sm:text-[26px] font-black tracking-tight text-[#1E105C] dark:text-white text-center">
                        Masuk ke Sistem
                    </h1>

                    <!-- Jasmine Floral Divider -->
                    <div class="flex items-center justify-center gap-3 my-2">
                        <span class="h-[1.5px] w-12 sm:w-14 bg-gradient-to-r from-transparent via-purple-300 to-purple-400 dark:via-purple-700 dark:to-purple-600 rounded-full"></span>
                        <img src="{{ asset('images/logo/melati-icon.svg') }}" class="size-5 sm:size-6 object-contain drop-shadow-xs" alt="Melati" />
                        <span class="h-[1.5px] w-12 sm:w-14 bg-gradient-to-l from-transparent via-purple-300 to-purple-400 dark:via-purple-700 dark:to-purple-600 rounded-full"></span>
                    </div>

                    <!-- Subtitle -->
                    <p class="text-xs sm:text-[12px] text-center font-medium text-slate-600 dark:text-slate-300 leading-snug max-w-[300px] mx-auto">
                        Sistem Informasi Monitoring, Evaluasi, Pelaporan Anggaran Terintegrasi
                    </p>

                    <!-- Error Alert Notice -->
                    @if($errorMessage)
                        <div class="mt-3 p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-300 flex items-start gap-2 text-xs leading-relaxed animate-fade-in">
                            <svg class="size-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <span class="font-bold block">Autentikasi Gagal</span>
                                <span>{{ $errorMessage }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Form Login -->
                    <form wire:submit.prevent="authenticate" class="mt-4 space-y-3">
                        <!-- Email Input -->
                        <div>
                            <label for="email" class="flex items-center gap-1.5 text-xs font-bold text-[#1E105C] dark:text-slate-200 mb-1">
                                <svg class="size-3.5 text-[#5B21B6] dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                <span>Alamat Email Pengguna</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-semibold text-sm">@</span>
                                <input id="email" type="email" wire:model="email" autofocus
                                       placeholder="username"
                                       class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-[#F1F3FB] dark:bg-slate-800/80 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-800 focus:border-[#5B21B6] focus:ring-2 focus:ring-[#5B21B6]/20 transition outline-none">
                            </div>
                            @error('email') <span class="text-xs text-rose-500 mt-0.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <!-- Password Input with Alpine Show/Hide -->
                        <div x-data="{ show: false }">
                            <label for="password" class="flex items-center gap-1.5 text-xs font-bold text-[#1E105C] dark:text-slate-200 mb-1">
                                <svg class="size-3.5 text-[#5B21B6] dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                                <span>Kata Sandi</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </span>
                                <input id="password" :type="show ? 'text' : 'password'" wire:model="password"
                                       placeholder="••••••••"
                                       class="w-full pl-9 pr-10 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-[#F1F3FB] dark:bg-slate-800/80 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-800 focus:border-[#5B21B6] focus:ring-2 focus:ring-[#5B21B6]/20 transition outline-none font-mono">
                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                    <svg x-show="!show" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="show" x-cloak class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password') <span class="text-xs text-rose-500 mt-0.5 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <!-- Remember Me Option -->
                        <div class="flex items-center justify-between pt-0.5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="remember"
                                       class="size-4 rounded border-slate-300 text-[#5B21B6] focus:ring-[#5B21B6] dark:border-slate-700 dark:bg-slate-800">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 select-none">Ingat saya di perangkat ini</span>
                            </label>
                        </div>

                        <!-- Submit Button with Gradient & Arrow Icon -->
                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full py-2.5 px-5 rounded-full bg-gradient-to-r from-[#7C3AED] via-[#6366F1] to-[#4C1D95] hover:from-[#6D28D9] hover:to-[#3B0764] text-white font-bold text-sm shadow-lg shadow-purple-600/30 hover:shadow-purple-600/40 active:scale-[0.99] transition-all flex items-center justify-center gap-2.5 data-loading:opacity-60 data-loading:pointer-events-none">
                                <span class="size-5 rounded-full border border-white/90 flex items-center justify-center shrink-0">
                                    <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path d="M5 12h14"/>
                                        <path d="m12 5 7 7-7 7"/>
                                    </svg>
                                </span>
                                <span>Masuk ke Sistem</span>
                                <svg class="size-4 hidden data-loading:inline-block animate-spin ml-1" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: 3 Feature Items -->
            <div class="flex flex-col gap-5 sm:gap-6 w-full max-w-[340px] shrink-0">
                <!-- Feature 1: Monitoring Realisasi Anggaran -->
                <div class="flex items-center gap-3.5">
                    <div class="size-12 rounded-2xl bg-white/90 dark:bg-slate-800/90 shadow-md shadow-purple-900/5 border border-white/80 dark:border-slate-700/80 p-2.5 flex items-center justify-center shrink-0">
                        <svg class="size-5.5 text-[#5B21B6] dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6" y1="20" x2="6" y2="14"/>
                        </svg>
                    </div>
                    <div class="h-8 w-[1.5px] bg-purple-300/80 dark:bg-purple-700/80 shrink-0"></div>
                    <div class="text-[14px] sm:text-[15px] font-bold text-[#1E105C] dark:text-white leading-tight">
                        Monitoring Realisasi<br>Anggaran
                    </div>
                </div>

                <!-- Feature 2: Evaluasi Capaian Program -->
                <div class="flex items-center gap-3.5">
                    <div class="size-12 rounded-2xl bg-white/90 dark:bg-slate-800/90 shadow-md shadow-purple-900/5 border border-white/80 dark:border-slate-700/80 p-2.5 flex items-center justify-center shrink-0">
                        <svg class="size-5.5 text-[#5B21B6] dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                            <rect x="9" y="3" width="6" height="4" rx="1"/>
                            <path d="m9 14 2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="h-8 w-[1.5px] bg-purple-300/80 dark:bg-purple-700/80 shrink-0"></div>
                    <div class="text-[14px] sm:text-[15px] font-bold text-[#1E105C] dark:text-white leading-tight">
                        Evaluasi Capaian<br>Program
                    </div>
                </div>

                <!-- Feature 3: Pelaporan Terintegrasi dan Akuntabel -->
                <div class="flex items-center gap-3.5">
                    <div class="size-12 rounded-2xl bg-white/90 dark:bg-slate-800/90 shadow-md shadow-purple-900/5 border border-white/80 dark:border-slate-700/80 p-2.5 flex items-center justify-center shrink-0">
                        <svg class="size-5.5 text-[#5B21B6] dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                            <polyline points="2 17 12 22 22 17"/>
                            <polyline points="2 12 12 17 22 12"/>
                        </svg>
                    </div>
                    <div class="h-8 w-[1.5px] bg-purple-300/80 dark:bg-purple-700/80 shrink-0"></div>
                    <div class="text-[14px] sm:text-[15px] font-bold text-[#1E105C] dark:text-white leading-tight">
                        Pelaporan Terintegrasi<br>dan Akuntabel
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-6 py-3 sm:py-4 text-center text-xs text-slate-500 dark:text-slate-400 z-10 relative">
        <p>
            &copy; {{ date('Y') }} Dinas Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan dan Perlindungan Anak (DP2KBP3A) Kabupaten Bangka.
        </p>
    </footer>
</div>
