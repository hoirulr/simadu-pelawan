<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentDownloadController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// --------------------------------------------------------------------------
// Public / Guest Routes
// --------------------------------------------------------------------------
Route::middleware('guest')->group(function (): void {
    Route::livewire('/', 'pages.auth.login')->name('login');
    Route::livewire('/login', 'pages.auth.login')->name('login.view');
    Route::livewire('/signin', 'pages.auth.login')->name('signin');
});

// --------------------------------------------------------------------------
// Authenticated Common Routes
// --------------------------------------------------------------------------
Route::middleware('auth')->group(function (): void {
    // Logout Handler
    Route::match(['get', 'post'], '/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // Secure Document & Evidence Downloads
    Route::get('/dokumen/{dokumen}/download', [DocumentDownloadController::class, 'downloadDokumenLaporan'])->name('dokumen.download');
    Route::get('/tindak-lanjut/{ticket}/download-bukti', [DocumentDownloadController::class, 'downloadBuktiKoreksi'])->name('ticket.download-bukti');

    // Locale Switcher (dari TailAdmin)
    Route::get('/locale/{locale}', function (string $locale) {
        if (in_array($locale, ['en', 'id', 'ar', 'es', 'de'], true)) {
            session()->put('locale', $locale);
        }

        return redirect()->back();
    })->name('locale.switch');

    // ----------------------------------------------------------------------
    // Admin Dinas DP2KBP3A Routes (Restricted by Role: admin)
    // ----------------------------------------------------------------------
    Route::middleware('role:admin')->group(function (): void {
        // Switch User Impersonation (Admin Only, Non-Production Only, POST + CSRF + Audit Log)
        Route::post('/switch-user/{id}', function (Request $request, int $id) {
            if (app()->isProduction()) {
                abort(403, 'Fitur switch user dinonaktifkan di lingkungan production.');
            }

            $targetUser = User::findOrFail($id);
            $currentUser = Auth::user();

            Log::info('User impersonation / switch user executed', [
                'actor_id' => $currentUser?->id,
                'actor_email' => $currentUser?->email,
                'actor_role' => $currentUser?->role?->value,
                'target_id' => $targetUser->id,
                'target_email' => $targetUser->email,
                'target_role' => $targetUser->role?->value,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'time' => now()->toIso8601String(),
            ]);

            Auth::login($targetUser);
            $request->session()->regenerate();

            if ($targetUser->isAdmin()) {
                return redirect()->route('dashboard');
            }

            return redirect()->route('balai.report');
        })->name('switch.user');

        // Dashboard Eksekutif & Peta Risiko 8 Balai
        Route::livewire('/dashboard', 'pages.dashboard.executive')->name('dashboard');
        Route::livewire('/dashboard-eksekutif', 'pages.dashboard.executive')->name('dashboard.index');

        // Meja Kerja Pemeriksaan & Skoring Admin (Livewire 4 Multi-File Component)
        Route::livewire('/admin/workspace', 'pages.admin.workspace')->name('admin.workspace');

        // Monitoring, Tiket Tindak Lanjut & Evaluasi Nilai Ekonomis
        Route::livewire('/monitoring', 'pages.monitoring.followup-economic')->name('monitoring.index');
        Route::livewire('/nilai-ekonomis', 'pages.monitoring.economic-evaluation')->name('economic.index');

        // Data Master
        Route::livewire('/master/wilayah', 'pages.master.wilayah')->name('master.wilayah');
        Route::livewire('/master/balai', 'pages.master.balai')->name('master.balai');
        Route::livewire('/master/periode', 'pages.master.periode')->name('master.periode');
        Route::livewire('/master/users', 'pages.master.users')->name('master.users');
    });

    // ----------------------------------------------------------------------
    // Balai Penyuluh KB Routes (Restricted by Role: operator_balai, admin)
    // ----------------------------------------------------------------------
    Route::middleware('role:operator_balai,admin')->group(function (): void {
        Route::livewire('/balai/laporan', 'pages.balai.report-submission')->name('balai.report');
        Route::livewire('/balai/status-risiko', 'pages.balai.status-risiko')->name('balai.status');
        Route::livewire('/balai/tindak-lanjut', 'pages.monitoring.followup-economic')->name('balai.tindak-lanjut');
    });
});
