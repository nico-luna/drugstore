<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Identity\UserController;
use App\Http\Controllers\Sales\NewSaleController;
use App\Http\Controllers\Sales\SaleHistoryController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Tenancy\TenantContextController;
use App\Http\Controllers\Tenancy\OrganizationController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Platform\OnboardingRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/solicitar-acceso', [PublicSiteController::class, 'requestAccess'])->name('access.request');
Route::post('/solicitar-acceso', [PublicSiteController::class, 'storeRequest'])
    ->middleware('throttle:5,10')
    ->name('access.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/olvide-mi-clave', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/olvide-mi-clave', [PasswordResetController::class, 'email'])
        ->middleware('throttle:5,10')
        ->name('password.email');
    Route::get('/restablecer-clave/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/restablecer-clave', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::post('/contexto', [TenantContextController::class, 'update'])->name('tenant.context.update');

    Route::middleware('platform-admin')->prefix('plataforma')->name('platform.')->group(function () {
        Route::get('/solicitudes', [OnboardingRequestController::class, 'index'])->name('requests.index');
        Route::put('/solicitudes/{id}', [OnboardingRequestController::class, 'update'])->name('requests.update');
    });

    Route::middleware('account-admin')->prefix('organizacion')->name('organization.')->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->name('index');
        Route::put('/cuenta', [OrganizationController::class, 'updateAccount'])->name('account.update');
        Route::post('/tiendas', [OrganizationController::class, 'storeStore'])->name('stores.store');
        Route::post('/tiendas/{store}/toggle', [OrganizationController::class, 'toggleStore'])->name('stores.toggle');
        Route::put('/miembros/{membership}', [OrganizationController::class, 'updateMember'])->name('members.update');
    });

    // Dashboard Module (Fase 1B.6)
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Usuarios Module (Fase 1B.3)
    Route::middleware('permission:usuarios')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{id}', [UserController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{id}/toggle', [UserController::class, 'toggle'])->name('usuarios.toggle');
    });

    // Clientes Module (Fase 1B.4)
    Route::middleware('permission:clientes')->group(function () {
        Route::get('/clientes', [CustomerController::class, 'index'])->name('clientes.index');
        Route::post('/clientes', [CustomerController::class, 'store'])->name('clientes.store');
        Route::put('/clientes/{id}', [CustomerController::class, 'update'])->name('clientes.update');
        Route::post('/clientes/{id}/toggle', [CustomerController::class, 'toggle'])->name('clientes.toggle');
    });

    // Productos Module (Fase 1B.5)
    Route::middleware('permission:productos')->group(function () {
        Route::get('/productos', [ProductController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductController::class, 'store'])->name('productos.store');
        Route::put('/productos/{id}', [ProductController::class, 'update'])->name('productos.update');
        Route::post('/productos/{id}/toggle', [ProductController::class, 'toggle'])->name('productos.toggle');
    });

    // Nueva Venta Module (Fase 1B.7)
    Route::middleware('permission:nueva_venta')->group(function () {
        Route::get('/nueva-venta', [NewSaleController::class, 'create'])->name('sales.create');
        Route::post('/nueva-venta', [NewSaleController::class, 'store'])->name('sales.store');
    });

    // Ventas / Historial Module (Fase 1B.8)
    Route::middleware('permission:ventas')->group(function () {
        Route::get('/ventas', [SaleHistoryController::class, 'index'])->name('ventas.index');
        Route::post('/ventas/{id}/cancel', [SaleHistoryController::class, 'cancel'])->name('ventas.cancel');
    });

    // Configuracion Module (Fase 1B.9)
    Route::middleware('permission:configuracion')->group(function () {
        Route::get('/configuracion', [SettingsController::class, 'edit'])->name('configuracion.edit');
        Route::put('/configuracion', [SettingsController::class, 'update'])->name('configuracion.update');
    });
});
