<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/payment', [PaymentController::class, 'index'])->name('payment');
Route::post('/payment', [PaymentController::class, 'store'])->name('payment_post');
Route::get('/stripe-3ds-result', [PaymentController::class, 'stripe3dsResult'])->name('stripe_3ds_result');