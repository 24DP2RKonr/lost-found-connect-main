<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:make-admin {email}', function (string $email) {
    /** @var User|null $user */
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error("User not found for email: {$email}");
        return self::FAILURE;
    }

    if (! array_key_exists('is_admin', $user->getAttributes())) {
        $this->error("Column 'is_admin' not present on users table. Did you run migrations?");
        return self::FAILURE;
    }

    $user->forceFill(['is_admin' => true])->save();

    $this->info("Admin enabled for: {$user->email}");
    return self::SUCCESS;
})->purpose('Grant admin privileges to a user by email');
