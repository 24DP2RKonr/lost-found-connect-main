<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

Route::get('/', function () {
    return view('welcome');
});

// Needed so Laravel's default verification email link works.
Route::get('/email/verify/{id}/{hash}', function (string $id, string $hash) {
    $user = User::query()->findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    // Redirect back to frontend (or fallback to home).
    return redirect(env('FRONTEND_URL', '/'));
})->middleware(['signed'])->name('verification.verify');
