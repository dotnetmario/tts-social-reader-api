<?php

use App\Http\Controllers\PayPalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', function(){
    return view('dashboard');
})->name('dashboard');


Route::post('/paypal/create-order', [PayPalController::class, 'createOrder'])->name('paypal.create');
Route::get('/paypal/success', [PayPalController::class, 'paymentSuccess'])->name('paypal.success');
Route::get('/paypal/cancel', function () {
    return redirect()->route('dashboard')->with('error', 'Payment cancelled.');
})->name('paypal.cancel');
