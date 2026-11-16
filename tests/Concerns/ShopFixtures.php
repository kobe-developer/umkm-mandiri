<?php

namespace Tests\Concerns;

use App\Enums\OrderChannel;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\OrderCreationService;

/** Data dasar toko untuk feature test. */
trait ShopFixtures
{
    protected StoreSetting $settings;

    protected PaymentMethod $cash;

    protected PaymentMethod $qris;

    protected function setUpShop(bool $open = true): void
    {
        $this->settings = StoreSetting::current();
        $this->settings->update(['is_open' => $open, 'online_order_enabled' => true, 'auto_schedule' => false]);
        $this->cash = PaymentMethod::factory()->create();
        $this->qris = PaymentMethod::factory()->qris()->create();
    }

    protected function cashier(): User
    {
        return User::factory()->create();
    }

    protected function owner(): User
    {
        return User::factory()->owner()->create();
    }

    protected function openShiftFor(User $user, int $openingCash = 100000): Shift
    {
        return Shift::create(['user_id' => $user->id, 'opened_at' => now(), 'opening_cash' => $openingCash]);
    }

    /** @return array<string, mixed> */
    protected function checkoutPayload(Product $product, int $quantity = 1, array $overrides = []): array
    {
        return [
            'items' => [['product_id' => $product->id, 'quantity' => $quantity, 'notes' => 'level 3']],
            'customer_name' => 'Budi',
            'whatsapp_number' => '0812-3456-7890',
            'fulfillment' => 'pickup',
            'payment_method_id' => $this->cash->id,
            'consent' => true,
            ...$overrides,
        ];
    }

    protected function placeOnlineOrder(Product $product, int $quantity = 1, string $whatsapp = '081234567890'): Order
    {
        return app(OrderCreationService::class)->createOnlineOrder(
            [...$this->checkoutPayload($product, $quantity), 'whatsapp_number' => '62'.substr($whatsapp, 1)],
            '127.0.0.1',
        );
    }

    protected function placePosOrder(User $cashier, Shift $shift, Product $product, int $quantity = 1, array $overrides = []): Order
    {
        return app(OrderCreationService::class)->createPosOrder([
            'channel' => OrderChannel::TakeAway->value,
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
            'payment_method_id' => $this->cash->id,
            'amount_received' => 1000000,
            'pay_later' => false,
            ...$overrides,
        ], $cashier, $shift);
    }
}
