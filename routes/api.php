<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\SerieTvController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Auth\ApiPasswordResetController;
use App\Http\Controllers\Auth\ApiForgotPasswordController;


// ROTTE PUBBLICHE
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/welcome', fn () => response()->json(['message' => 'Benvenuto nel sistema']));
Route::post('/reset-password', [ApiPasswordResetController::class, 'store']);
Route::post('/forgot-password', [ApiForgotPasswordController::class, 'store']);

// ROTTE AUTENTICATE (User + Admin)
Route::middleware(['jwt', 'log.user.access', 'can:blockGuests,App\Models\User'])->group(function () {

    Route::get('/languages', [LanguageController::class, 'index']);

    // FILM
    Route::get('/movies', [MovieController::class, 'index']);
    Route::get('/movies/{movie}', [MovieController::class, 'show']);
    Route::post('/movies', [MovieController::class, 'store'])->middleware('can:create,App\Models\Movie');
    Route::put('/movies/{movie}', [MovieController::class, 'update'])->middleware('can:update,movie');
    Route::delete('/movies/{movie}', [MovieController::class, 'destroy'])->middleware('can:delete,movie');

    // SERIE TV
    Route::get('/series', [SerieTvController::class, 'index']);
    Route::get('/series/{serie_tv}', [SerieTvController::class, 'show']);
    Route::post('/series', [SerieTvController::class, 'store'])->middleware('can:create,App\Models\SerieTv');
    Route::put('/series/{serie_tv}', [SerieTvController::class, 'update'])->middleware('can:update,serie_tv');
    Route::delete('/series/{serie_tv}', [SerieTvController::class, 'destroy'])->middleware('can:delete,serie_tv');

    // EPISODI
    Route::get('/episodes', [EpisodeController::class, 'index']);
    Route::get('/episodes/{episode}', [EpisodeController::class, 'show']);
    Route::post('/episodes', [EpisodeController::class, 'store'])->middleware('can:create,App\Models\Episode');
    Route::put('/episodes/{episode}', [EpisodeController::class, 'update'])->middleware('can:update,episode');
    Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])->middleware('can:delete,episode');

    // CATEGORIE
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('can:adminAccess,App\Models\User');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('can:adminAccess,App\Models\User');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('can:adminAccess,App\Models\User');

    // UTENTI
    Route::get('/users', [UserController::class, 'index'])->middleware('can:adminAccess,App\Models\User');
    Route::post('/users', [UserController::class, 'store'])->middleware('can:create,App\Models\User'); 
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('can:view,user');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('can:update,user');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('can:adminAccess,App\Models\User');

    // CREDITI
    Route::post('/credits/add', [UserController::class, 'addCredits']);
});

if (app()->environment('local')) {
    Route::get('/test-login-hash', [\App\Http\Controllers\AuthController::class, 'testLoginHashOnly']);
}


