<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun awal. WAJIB ganti password & PIN setelah login pertama.
 * Password awal bisa diatur lewat env SEED_OWNER_PASSWORD / SEED_CASHIER_PASSWORD.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(['username' => 'owner'], [
            'name' => 'Owner',
            'role' => Role::Owner,
            'password' => env('SEED_OWNER_PASSWORD', 'password'),
            'is_active' => true,
        ]);

        if (! $owner->hasPin()) {
            $owner->forceFill(['pin_hash' => Hash::make((string) env('OWNER_DEFAULT_PIN', '123456'))])->save();
        }

        User::query()->firstOrCreate(['username' => 'kasir'], [
            'name' => 'Kasir 1',
            'role' => Role::Cashier,
            'password' => env('SEED_CASHIER_PASSWORD', 'password'),
            'is_active' => true,
        ]);
    }
}
