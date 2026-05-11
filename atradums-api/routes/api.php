<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Models\Profile;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['success' => true]);
    })->middleware('throttle:6,1');
    Route::apiResource('listings', ListingController::class)->except(['index','show']);
    Route::apiResource('conversations', ConversationController::class)->only(['index','store']);
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::put('conversations/{conversation}/read', [MessageController::class, 'markConversationAsRead']);
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::delete('/admin/users/{user}', [AdminController::class, 'destroy']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
});

Route::get('/listings', [ListingController::class, 'index']);
Route::get('/listings/{listing}', [ListingController::class, 'show']);

// Public profile lookup (for listing author card)
Route::get('/profiles/{userId}', function (string $userId) {
    return Profile::query()
        ->where('user_id', $userId)
        ->firstOrFail(['user_id', 'name', 'avatar_url', 'location']);
});
