<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Design\CreateLayout\CreateLayoutController;
use App\Http\Controllers\Design\GenerateImage\GenerateImageController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Hello World'
    ]);
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::post('/generateImage', [GenerateImageController::class, 'generateImage']);
    Route::post('regenerateImage', [GenerateImageController::class, 'regenerateImage']);
    Route::post('/generateLayout', [CreateLayoutController::class, 'generateLayout']);
});
