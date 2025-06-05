<?php

use App\Http\Controllers\AdvertController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckUserRoleIsAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use App\Http\Middleware\CheckUserRoleIsSuperAdmin;

//Route::get('/', function () {
//    return view('welcome');
//});

Auth::routes();

//Route::match(['get', 'post'], 'register', function(){
//    return redirect('/');
//});

Route::redirect('/', '/dashboard');
Route::redirect('/home', '/dashboard');

Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard')->middleware(CheckUserRoleIsAdmin::class);
Route::post('/export', [HomeController::class, 'export'])->middleware(CheckUserRoleIsAdmin::class);

Route::get('/products', [ProductController::class, 'index'])->name('product.index');
Route::post('/product', [ProductController::class, 'create'])->name('product.create');
Route::post('/product/{id}', [ProductController::class, 'update'])->name('product.update');
Route::delete('/product/{id}', [ProductController::class, 'delete'])->name('product.delete');

Route::get('/adverts', [AdvertController::class, 'index'])->name('advert.index')->middleware(CheckUserRoleIsAdmin::class);
Route::post('/advert', [AdvertController::class, 'create'])->name('advert.create')->middleware(CheckUserRoleIsAdmin::class);
Route::post('/advert/{id}', [AdvertController::class, 'update'])->name('advert.update')->middleware(CheckUserRoleIsAdmin::class);
Route::delete('/advert/{id}', [AdvertController::class, 'delete'])->name('advert.delete')->middleware(CheckUserRoleIsAdmin::class);
Route::post('/adverts/order', [AdvertController::class, 'newOrder'])->name('advert.order')->middleware(CheckUserRoleIsAdmin::class);
Route::get('/users', [HomeController::class, 'users'])->name('users.index')->middleware(CheckUserRoleIsAdmin::class);
Route::delete('/user/{id}', [HomeController::class, 'deleteUser'])->name('users.delete')->middleware(CheckUserRoleIsAdmin::class);
Route::post('/user/role/{id}/{newRole}', [HomeController::class, 'changeUserRole'])->name('users.change.role')->middleware(CheckUserRoleIsAdmin::class);

Route::get('/machines', [MachineController::class, 'index'])->name('machines.index');
Route::post('/machine/{id}', [MachineController::class, 'update'])->name('machines.update');
Route::get('/machine/{id}', [MachineController::class, 'machineInventory']);
Route::post('/change/password', [HomeController::class, 'changePassword'])->name('change.password');

Route::get('/register/{id}', [RegisterController::class, 'showRealRegistrationForm'])->name('users.register');

// Settings routes (Super Admin only)
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware(CheckUserRoleIsSuperAdmin::class);
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware(CheckUserRoleIsSuperAdmin::class);

// Transaction details route (Super Admin only)
Route::get('/transaction/{id}', [HomeController::class, 'transactionDetails'])->name('transaction.details')->middleware(CheckUserRoleIsSuperAdmin::class);
