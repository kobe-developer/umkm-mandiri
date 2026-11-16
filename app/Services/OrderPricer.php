<?php

namespace App\Services;

use App\Enums\OrderChannel;
use App\Models\Product;
use App\Support\BusinessRule;

/**
 * Menghitung ulang harga di server dari tabel products.
 * Harga dari browser tidak pernah dipakai.
 */
class OrderPricer
{
    /**
     * @param  list<array{product_id: int, quantity: int, notes?: ?string}>  $items
     * @return array{lines: list<array<string, mixed>>, subtotal: int}
     */
    public function price(array $items, OrderChannel $channel): array
    {
        BusinessRule::check($items !== [], 'Keranjang masih kosong.', 'items');

        $products = Product::with('category')->whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');

        $unavailable = [];
        $lines = [];
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            if (! $product || ! $this->isOrderable($product, $channel)) {
                $unavailable[] = $product->name ?? 'Produk tidak dikenal';

                continue;
            }

            $quantity = (int) $item['quantity'];
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category_name' => $product->category?->name,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'notes' => $this->cleanNotes($item['notes'] ?? null),
                'subtotal' => $product->price * $quantity,
            ];
        }

        if ($unavailable !== []) {
            BusinessRule::fail('Produk berikut sedang habis/tidak tersedia: '.implode(', ', array_unique($unavailable)).'. Silakan perbarui keranjang.', 'items');
        }

        return ['lines' => $lines, 'subtotal' => array_sum(array_column($lines, 'subtotal'))];
    }

    public function discount(int $subtotal, ?string $type, int $value): int
    {
        return match ($type) {
            'percent' => (int) floor($subtotal * min($value, 100) / 100),
            'nominal' => min($value, $subtotal),
            default => 0,
        };
    }

    private function isOrderable(Product $product, OrderChannel $channel): bool
    {
        if (! $product->isSellable()) {
            return false;
        }

        return $channel !== OrderChannel::Online || $product->show_online;
    }

    private function cleanNotes(?string $notes): ?string
    {
        $notes = trim((string) $notes);

        return $notes === '' ? null : mb_substr($notes, 0, 100);
    }
}
