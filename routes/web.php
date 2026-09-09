<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Catalog\ProductController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Identity\UserController;
use App\Http\Controllers\Sales\NewSaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

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
});
