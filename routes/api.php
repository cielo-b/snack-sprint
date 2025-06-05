<?php

use App\Http\Controllers\AdvertController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/initiate-payment', [PaymentController::class, 'initializePayment']);

Route::match(array('GET','POST'), '/payment-callback', [PaymentController::class, 'callback']);

Route::get('/sync/products', [ SyncController::class, 'syncProducts' ]);
Route::get('/sync/adverts', [ SyncController::class, 'syncAdverts' ]);
Route::get('/sync/state', [ SyncController::class, 'syncState' ]);
Route::post('/sync/state/{machineId}', [ SyncController::class, 'syncStateNew' ]);

Route::post('/machine-error', [SyncController::class, 'machineError']);
Route::post('/delivery-report', [PaymentController::class, 'deliveryReport']);

