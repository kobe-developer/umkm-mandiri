<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->word().fake()->randomNumber(3), 'sort_order' => 1, 'is_active' => true];
    }
}
