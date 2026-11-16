<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'price', 'photo_path', 'description', 'is_active',
        'show_online', 'track_stock', 'stock', 'stock_minimum', 'is_available', 'sort_order',
    ];

    protected $appends = ['photo_url', 'is_sold_out', 'is_low_stock'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'stock_minimum' => 'integer',
            'is_active' => 'boolean',
            'show_online' => 'boolean',
            'track_stock' => 'boolean',
            'is_available' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Habis = ditandai habis manual, atau stok dilacak dan sudah 0. */
    protected function isSoldOut(): Attribute
    {
        return Attribute::get(fn () => ! $this->is_available || ($this->track_stock && $this->stock <= 0));
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::get(fn () => $this->track_stock && $this->stock <= $this->stock_minimum);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null);
    }

    public function isSellable(): bool
    {
        return $this->is_active && ! $this->is_sold_out;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->active()->where('show_online', true)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->active()->where('track_stock', true)->whereColumn('stock', '<=', 'stock_minimum');
    }
}
