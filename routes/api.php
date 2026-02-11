<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// User CRUD routes
Route::apiResource('users', UserController::class);

// Optional: Get authenticated user (Sanctum)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
