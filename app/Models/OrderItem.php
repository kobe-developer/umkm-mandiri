<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'category_name',
        'unit_price', 'original_price', 'promotion_id', 'quantity', 'notes', 'subtotal',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'original_price' => 'integer', 'quantity' => 'integer', 'subtotal' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /** Potongan dari promo untuk baris ini (harga normal − harga promo) × qty. */
    public function promoSaving(): int
    {
        return $this->original_price !== null ? max(0, $this->original_price - $this->unit_price) * $this->quantity : 0;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
