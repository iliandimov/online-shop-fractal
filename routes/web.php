<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('category');
Route::get('/product/{slug}', [StorefrontController::class, 'product'])->name('product');
Route::get('/cart', [StorefrontController::class, 'cart'])->name('cart');
Route::post('/cart/items', [StorefrontController::class, 'addToCart'])->name('cart.add');
Route::patch('/cart/items/{lineId}', [StorefrontController::class, 'updateCart'])->name('cart.update');
Route::delete('/cart/items/{lineId}', [StorefrontController::class, 'removeFromCart'])->name('cart.remove');
Route::get('/checkout', [StorefrontController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [StorefrontController::class, 'placeOrder'])->name('checkout.place');
Route::get('/order-complete', [StorefrontController::class, 'orderComplete'])->name('order.complete');
