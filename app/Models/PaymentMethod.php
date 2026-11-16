<?php

namespace App\Models;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'bank_name', 'account_number', 'account_holder',
        'qris_image_path', 'channels', 'is_active', 'sort_order',
    ];

    protected $appends = ['qris_image_url'];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'channels' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected function qrisImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->qris_image_path ? Storage::disk('public')->url($this->qris_image_path) : null);
    }

    public function isCash(): bool
    {
        return $this->type === PaymentMethodType::Cash;
    }

    public function supportsChannel(OrderChannel $channel): bool
    {
        return $this->is_active && in_array($channel->value, $this->channels ?? [], true);
    }

    public function scopeForChannel(Builder $query, OrderChannel $channel): Builder
    {
        return $query->where('is_active', true)
            ->whereJsonContains('channels', $channel->value)
            ->orderBy('sort_order')->orderBy('name');
    }
}
