<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

test('unauthenticated guests are redirected to login when accessing protected pages', function (): void {
    $protectedUrls = [
        '/dashboard',
        '/admin/workspace',
        '/master/users',
        '/master/periode',
        '/balai/laporan',
        '/balai/status-risiko',
    ];

    foreach ($protectedUrls as $url) {
        $response = $this->get($url);
        $response->assertRedirect('/');
    }
});

test('authenticated admin visiting login is redirected to dashboard', function (): void {
    $admin = User::where('role', UserRole::Admin)->first();

    $response = $this->actingAs($admin)->get('/login');
    $response->assertRedirect('/dashboard');
});

test('authenticated operator balai visiting login is redirected to balai laporan', function (): void {
    $operator = User::where('role', UserRole::OperatorBalai)->first();

    $response = $this->actingAs($operator)->get('/login');
    $response->assertRedirect('/balai/laporan');
});

test('admin can log in with valid credentials and redirect to dashboard', function (): void {
    Livewire::test('pages.auth.login')
        ->set('email', 'admin@simadupelawan.go.id')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect('/dashboard');

    $this->assertAuthenticated();
});

test('operator balai can log in with valid credentials and redirect to balai laporan', function (): void {
    Livewire::test('pages.auth.login')
        ->set('email', 'operator.sungailiat@simadupelawan.go.id')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertRedirect('/balai/laporan');

    $this->assertAuthenticated();
});

test('login fails with invalid credentials and displays error message', function (): void {
    Livewire::test('pages.auth.login')
        ->set('email', 'admin@simadupelawan.go.id')
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertSee('Alamat email atau kata sandi yang Anda masukkan salah.');

    $this->assertGuest();
});

test('admin can access all admin and master pages', function (): void {
    $admin = User::where('role', UserRole::Admin)->first();

    $adminPages = [
        '/dashboard',
        '/admin/workspace',
        '/monitoring',
        '/nilai-ekonomis',
        '/master/wilayah',
        '/master/balai',
        '/master/periode',
        '/master/users',
    ];

    foreach ($adminPages as $page) {
        $response = $this->actingAs($admin)->get($page);
        $response->assertOk();
    }
});

test('operator balai cannot access admin meja kerja or master data and receives 403', function (): void {
    $operator = User::where('role', UserRole::OperatorBalai)->first();

    $forbiddenPages = [
        '/dashboard',
        '/admin/workspace',
        '/master/users',
        '/master/periode',
        '/master/balai',
        '/master/wilayah',
    ];

    foreach ($forbiddenPages as $page) {
        $response = $this->actingAs($operator)->get($page);
        $response->assertForbidden();
    }
});

test('operator balai can access balai specific pages', function (): void {
    $operator = User::where('role', UserRole::OperatorBalai)->first();

    $balaiPages = [
        '/balai/laporan',
        '/balai/status-risiko',
        '/balai/tindak-lanjut',
    ];

    foreach ($balaiPages as $page) {
        $response = $this->actingAs($operator)->get($page);
        $response->assertOk();
    }
});

test('user can log out and session is invalidated', function (): void {
    $admin = User::where('role', UserRole::Admin)->first();

    $response = $this->actingAs($admin)->get('/logout');
    $response->assertRedirect('/');

    $this->assertGuest();
});
