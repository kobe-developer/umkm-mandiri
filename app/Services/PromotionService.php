<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Promotion;
use App\Support\BusinessRule;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;

/** Tambah/ubah promo dengan aturan: harga promo < harga normal, periode tidak bentrok per produk. */
class PromotionService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /** @param  array<string, mixed>  $data  data tervalidasi dari PromotionRequest */
    public function create(array $data): Promotion
    {
        $promotion = DB::transaction(function () use ($data) {
            $product = $this->lockProduct((int) $data['product_id']);
            $this->assertValid($product, $data, null);

            return Promotion::create($this->attributes($data));
        });

        $this->logger->log('create', $promotion, null, $promotion->getAttributes(), "Tambah promo {$promotion->product->name}");

        return $promotion;
    }

    /** @param  array<string, mixed>  $data */
    public function update(Promotion $promotion, array $data): Promotion
    {
        $original = $promotion->getAttributes();

        DB::transaction(function () use ($promotion, $data) {
            $product = $this->lockProduct((int) $data['product_id']);
            $this->assertValid($product, $data, $promotion->id);
            $promotion->update($this->attributes($data));
        });

        $this->logger->logChanges('update', $promotion, $original, "Ubah promo {$promotion->product->name}");

        return $promotion;
    }

    public function delete(Promotion $promotion): void
    {
        $promotion->delete();
        $this->logger->log('delete', $promotion, $promotion->only(['product_id', 'price', 'active_from', 'active_until']), null, "Hapus promo {$promotion->product->name}");
    }

    /** Kunci baris produk agar dua penyimpanan bersamaan tidak sama-sama lolos cek bentrok. */
    private function lockProduct(int $productId): Product
    {
        return Product::whereKey($productId)->lockForUpdate()->firstOrFail();
    }

    /** @param  array<string, mixed>  $data */
    private function assertValid(Product $product, array $data, ?int $ignoreId): void
    {
        BusinessRule::check(
            (int) $data['price'] < $product->price,
            'Harga promo harus lebih kecil dari harga normal '.Rupiah::format($product->price).'.',
            'price',
        );

        $clash = Promotion::where('product_id', $product->id)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->overlapping($data['active_from'], $data['active_until'])
            ->orderBy('active_from')
            ->first();

        BusinessRule::check(
            $clash === null,
            $clash ? "{$product->name} sudah punya promo pada {$clash->active_from->translatedFormat('j M Y')} – {$clash->active_until->translatedFormat('j M Y')}. Periode promo tidak boleh bentrok." : '',
            'active_from',
        );
    }

    /** @param  array<string, mixed>  $data */
    private function attributes(array $data): array
    {
        $description = trim((string) ($data['description'] ?? ''));

        return [
            'product_id' => (int) $data['product_id'],
            'price' => (int) $data['price'],
            'channels' => array_values(array_unique($data['channels'])),
            'active_from' => $data['active_from'],
            'active_until' => $data['active_until'],
            'description' => $description === '' ? null : $description,
        ];
    }
}
