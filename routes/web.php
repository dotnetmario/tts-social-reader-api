<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PayPalController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // return view('welcome');
    return view('dashboard');
});

Route::get('/dashboard', function(){
    return view('dashboard');
})->name('dashboard');

/**
 * Authentication
 */

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'performLogin'])->name('login');

Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'performRegister']);

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/login');
})->name('logout');

/**
 * /Authentication
 */



Route::post('/paypal/create-order', [PayPalController::class, 'createOrder'])->name('paypal.create');
Route::get('/paypal/success', [PayPalController::class, 'paymentSuccess'])->name('paypal.success');
Route::get('/paypal/cancel', function () {
    return redirect()->route('dashboard')->with('error', 'Payment cancelled.');
})->name('paypal.cancel');


Route::get('/credits', function(){
    $user = User::find(2);
    $credits = $user->getAvailableCredits();

    dd([
        'user' => $user->toArray(),
        'available chars' => $user->getAvailableCharacterCount(),
        'credits' => $credits->toArray()
    ]);
});
