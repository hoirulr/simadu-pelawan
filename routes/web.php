<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    // Quick Demo Auth Switcher (Admin <-> Operator Balai)
    Route::get('/switch-user/{id}', function (int $id) {
        $user = User::find($id);
        if ($user) {
            Auth::login($user);
        }

        return redirect()->back();
    })->name('switch.user');

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
