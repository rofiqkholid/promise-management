<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


Route::get('/login', function () {
    if (request()->has('redirect')) {
        session()->put('url.intended', request()->get('redirect'));
    }
    return redirect(env('PORTAL_LOGIN_URL', 'https://promise.summitadyawinsa.co.id/login'));
})->name('login');

Route::get('/', function () {
    Log::info('Inventory SSO Check', [
        'session_id' => session()->getId(),
        'cookie_val' => request()->cookie('promise_auth_session'),
        'auth_check' => Auth::check(),
        'user_id' => Auth::id(),
    ]);
    if (Auth::check()) {
        return redirect()->intended(route('dashboard'));
    }
    return redirect()->route('login');
});

Route::post('/login', function () {
    return redirect()->route('login');
})->name('login_post');
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    return redirect(env('PORTAL_LOGIN_URL', 'https://promise.summitadyawinsa.co.id/login'));
})->name('logout');

Route::get('/dashboard', function () {
    return view('welcome');
})->middleware('auth')->name('dashboard');
