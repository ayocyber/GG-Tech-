<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the first staff account. Set ADMIN_EMAIL and ADMIN_PASSWORD in .env to choose them.
     */
    public function run(): void
    {
        $email = config('store.admin.email');

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $configuredPassword = config('store.admin.password');
        $password = $configuredPassword ?: Str::password(16, symbols: false);

        User::query()->create([
            'name' => 'GG-TECH Admin',
            'email' => $email,
            'password' => $password,
        ]);

        $this->command?->info("Admin account: {$email}".($configuredPassword ? '' : " / password: {$password}"));
    }
}
