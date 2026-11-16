<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->unique()->words(2, true),
            'price' => 10000,
            'is_active' => true,
            'show_online' => true,
            'track_stock' => false,
            'stock' => 0,
            'stock_minimum' => 0,
            'is_available' => true,
        ];
    }

    public function tracked(int $stock, int $minimum = 0): static
    {
        return $this->state(fn () => ['track_stock' => true, 'stock' => $stock, 'stock_minimum' => $minimum]);
    }
}
