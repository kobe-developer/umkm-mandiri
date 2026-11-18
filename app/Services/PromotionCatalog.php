<?php

namespace App\Services;

use App\Enums\OrderChannel;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal harga promo yang berlaku hari ini. Dipakai saat menghitung harga order
 * (server) maupun saat menampilkan harga coret di menu online & POS.
 */
class PromotionCatalog
{
    /**
     * Promo yang berlaku hari ini, satu per produk (aturan bisnis melarang periode bentrok;
     * bila data lama tetap bentrok, harga termurah yang dipakai agar hasil deterministik).
     *
     * @param  iterable<int>  $productIds
     * @return Collection<int, Promotion> dikunci product_id
     */
    public function activeFor(iterable $productIds): Collection
    {
        $ids = collect($productIds)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Promotion::query()
            ->whereIn('product_id', $ids)
            ->activeOn()
            ->orderBy('price')
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
    }

    /**
     * Harga satuan untuk kanal tertentu. Promo diabaikan bila tidak berlaku di kanal itu,
     * atau bila harga normal produk kini sudah sama/lebih murah dari harga promo.
     *
     * @return array{unit_price: int, original_price: int, promotion_id: ?int}
     */
    public function priceFor(Product $product, ?Promotion $promotion, OrderChannel $channel): array
    {
        $applies = $promotion !== null
            && $promotion->appliesTo($channel)
            && $promotion->price < $product->price;

        return [
            'unit_price' => $applies ? $promotion->price : $product->price,
            'original_price' => $product->price,
            'promotion_id' => $applies ? $promotion->id : null,
        ];
    }

    /**
     * Data promo untuk ditampilkan ke frontend (POS memilih harga sesuai kanal di browser,
     * tetapi harga final tetap dihitung ulang di server).
     *
     * @return ?array{price: int, channels: list<string>, description: ?string, active_until: string}
     */
    public function present(Product $product, ?Promotion $promotion): ?array
    {
        if ($promotion === null || $promotion->price >= $product->price) {
            return null;
        }

        return [
            'price' => $promotion->price,
            'channels' => array_values($promotion->channels ?? []),
            'description' => $promotion->description,
            'active_until' => $promotion->active_until->toDateString(),
        ];
    }
}
