<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PuntoventaController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\VentaController;


// Ruta para la página de bienvenida, accesible para todos los usuarios
Route::get('/', [WelcomeController::class, 'showWelcomePage'])->name('welcome');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/', [WelcomeController::class, 'showWelcomePage'])->name('welcome');


Route::post('/usuarios/changePassword', [App\Http\Controllers\UsuarioController::class, 'changePassword'])->name('usuarios.changePassword');
Route::post('/usuarios/updateProfile', [App\Http\Controllers\UsuarioController::class, 'updateProfile'])->name('usuarios.updateProfile');
Route::get('/usuarios/user-list', [App\Http\Controllers\UsuarioController::class, 'getUserList'])->name('usuarios.getUserList');

// Grupo de rutas protegidas por el middleware 'auth'
Route::group(['middleware' => ['auth']], function() {
    Route::resource('roles', RolController::class);
    Route::resource('usuarios', UsuarioController::class);
    Route::resource('productos', ProductoController::class); 
    Route::resource('materias', MateriaController::class);
    
    // routes/web.php
    Route::resource('ventas', VentaController::class);
    Route::get('/ventas/crear', [VentaController::class, 'create']);

    Route::post('/productos/updateCantidad/{id}', [ProductoController::class, 'updateCantidad'])->name('productos.updateCantidad');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
    Route::get('/ventas/{id}/detalles', [VentaController::class, 'detalles']);
    Route::get('/ventas/{id}/pdf', [VentaController::class, 'generarPDF'])->name('ventas.pdf');

    Route::resource('logs', LogController::class);
    //Route::get('/grupos/{clave}/generarPDF', [GrupoController::class, 'generarPDF'])->name('grupos.generarPDF');
}

);