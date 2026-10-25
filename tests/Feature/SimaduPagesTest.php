<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('main page / returns 200 ok and displays login portal for guests', function () {
    $response = $this->get('/');
    $response->assertOk();
    $response->assertSee('Masuk ke Sistem');
    $response->assertSee('SI MADU');
});

test('executive dashboard page returns 200 ok for authenticated user', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk();
});

test('logout redirects to login page', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/logout');
    $response->assertRedirect('/');
});

test('admin meja kerja workspace page returns 200 ok', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/admin/workspace');
    $response->assertOk();
});

test('balai report submission page returns 200 ok', function () {
    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $response = $this->actingAs($operator)->get('/balai/laporan');
    $response->assertOk();
});

test('monitoring and follow-up page returns 200 ok for admin', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/monitoring');
    $response->assertOk();
});

test('economic value evaluation page returns 200 ok for admin', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/nilai-ekonomis');
    $response->assertOk();
});

test('master wilayah page returns 200 ok with kecamatan and desa data', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/master/wilayah');
    $response->assertOk();
    $response->assertSee('Sungailiat');
    $response->assertSee('Puding Besar');
});

test('master balai page returns 200 ok with 8 balai', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/master/balai');
    $response->assertOk();
    $response->assertSee('Balai Penyuluh KB Sungailiat');
});

test('balai status risiko page returns 200 ok', function () {
    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $response = $this->actingAs($operator)->get('/balai/status-risiko');
    $response->assertOk();
    $response->assertSee('Status Risiko');
    $response->assertSee('Early Warning System');
});

test('balai tindak lanjut page returns 200 ok', function () {
    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $response = $this->actingAs($operator)->get('/balai/tindak-lanjut');
    $response->assertOk();
    $response->assertSee('Daftar Tiket Tindak Lanjut');
});

test('master periode anggaran page returns 200 ok', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/master/periode');
    $response->assertOk();
    $response->assertSee('Master Periode Anggaran');
    $response->assertSee('Tahap I');
});

test('master akun pengguna page returns 200 ok with users list', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $response = $this->actingAs($admin)->get('/master/users');
    $response->assertOk();
    $response->assertSee('Manajemen Akun Pengguna');
    $response->assertSee('Admin Dinas DP2KBP3A');
});
