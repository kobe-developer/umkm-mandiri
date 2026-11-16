<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName().Str::random(3),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Cashier,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(?string $pin = '123456'): static
    {
        return $this->state(fn () => [
            'role' => Role::Owner,
        ])->afterCreating(function (User $user) use ($pin) {
            if ($pin !== null) {
                $user->forceFill(['pin_hash' => Hash::make($pin)])->save();
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
