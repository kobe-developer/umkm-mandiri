<?php

namespace Database\Factories;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentMethod> */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Tunai',
            'type' => PaymentMethodType::Cash,
            'channels' => OrderChannel::values(),
            'is_active' => true,
        ];
    }

    public function qris(): static
    {
        return $this->state(fn () => ['name' => 'QRIS', 'type' => PaymentMethodType::Qris]);
    }

    public function channels(array $channels): static
    {
        return $this->state(fn () => ['channels' => $channels]);
    }
}
