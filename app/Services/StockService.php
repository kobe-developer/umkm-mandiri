<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\BusinessRule;

/**
 * Stok per produk. Semua method harus dipanggil di dalam DB::transaction
 * karena memakai lockForUpdate() agar stok tidak minus saat order bersamaan.
 */
class StockService
{
    public function deductForOrder(Order $order, ?User $user): void
    {
        if ($order->stock_deducted) {
            return;
        }

        $products = $this->lockTrackedProducts($order);
        $required = $order->items->groupBy('product_id')->map->sum('quantity');

        foreach ($required as $productId => $quantity) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }
            BusinessRule::check(
                $product->stock >= $quantity,
                "Stok {$product->name} tidak cukup (sisa {$product->stock}).",
                'stock',
            );
        }

        foreach ($required as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product) {
                $this->move($product, StockMovementType::Out, -$quantity, "Order {$order->order_number}", $user, $order);
            }
        }

        $order->forceFill(['stock_deducted' => true])->save();
    }

    public function restoreForOrder(Order $order, ?User $user, string $reason): void
    {
        if (! $order->stock_deducted) {
            return;
        }

        $products = $this->lockTrackedProducts($order);
        foreach ($order->items->groupBy('product_id')->map->sum('quantity') as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product) {
                $this->move($product, StockMovementType::Return, $quantity, $reason, $user, $order);
            }
        }

        $order->forceFill(['stock_deducted' => false])->save();
    }

    /** Stok masuk (qty positif) atau penyesuaian (qty +/-), alasan wajib untuk penyesuaian. */
    public function adjust(Product $product, StockMovementType $type, int $quantity, ?string $reason, User $user): StockMovement
    {
        $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();

        BusinessRule::check($locked->stock + $quantity >= 0, "Stok {$locked->name} tidak boleh minus.", 'quantity');

        return $this->move($locked, $type, $quantity, $reason, $user);
    }

    private function move(Product $product, StockMovementType $type, int $quantity, ?string $reason, ?User $user, ?Order $order = null): StockMovement
    {
        $product->stock += $quantity;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_after' => $product->stock,
            'reason' => $reason,
            'order_id' => $order?->id,
            'user_id' => $user?->id,
        ]);
    }

    private function lockTrackedProducts(Order $order)
    {
        $order->loadMissing('items');

        return Product::withTrashed()
            ->whereIn('id', $order->items->pluck('product_id')->filter())
            ->where('track_stock', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
