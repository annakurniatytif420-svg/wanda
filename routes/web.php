<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json(['message' => 'Redirected to login (web)'], 401);
})->name('login');

// Web routes only for frontend, SPA fallback, or welcome page.
Route::get('/', function () {
    return view('welcome');
});

//// Optional: If you serve the Vue app via Laravel (not recommended when using Vite dev server)
// Route::view('/{any}', 'app')->where('any', '.*');
