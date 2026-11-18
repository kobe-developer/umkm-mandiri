<?php

namespace Database\Factories;

use App\Enums\OrderChannel;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Promotion> */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'price' => 8000,
            'channels' => OrderChannel::values(),
            'active_from' => today()->toDateString(),
            'active_until' => today()->addDays(7)->toDateString(),
            'description' => 'Promo spesial',
        ];
    }

    /** @param  list<OrderChannel>  $channels */
    public function channels(array $channels): static
    {
        return $this->state(fn () => ['channels' => array_map(fn (OrderChannel $c) => $c->value, $channels)]);
    }

    public function between(string $from, string $until): static
    {
        return $this->state(fn () => ['active_from' => $from, 'active_until' => $until]);
    }
}
