<?php

namespace App\Models;

use App\Enums\OrderChannel;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Harga promo sebuah produk. Berlaku sejak awal hari active_from sampai akhir hari
 * active_until (WIB), hanya untuk kanal yang tercantum di channels.
 */
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'berlaku';

    public const STATUS_SCHEDULED = 'terjadwal';

    public const STATUS_EXPIRED = 'berakhir';

    protected $fillable = ['product_id', 'price', 'channels', 'active_from', 'active_until', 'description'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'channels' => 'array',
            'active_from' => 'date',
            'active_until' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function appliesTo(OrderChannel $channel): bool
    {
        return in_array($channel->value, $this->channels ?? [], true);
    }

    public function status(?Carbon $today = null): string
    {
        $today = ($today ?? today())->toDateString();

        return match (true) {
            $this->active_from->toDateString() > $today => self::STATUS_SCHEDULED,
            $this->active_until->toDateString() < $today => self::STATUS_EXPIRED,
            default => self::STATUS_ACTIVE,
        };
    }

    /** Promo yang berlaku pada tanggal tertentu (default hari ini). */
    public function scopeActiveOn(Builder $query, ?Carbon $date = null): Builder
    {
        $day = ($date ?? today())->toDateString();

        return $query->whereDate('active_from', '<=', $day)->whereDate('active_until', '>=', $day);
    }

    /** Promo yang periodenya beririsan dengan rentang tanggal [from, until]. */
    public function scopeOverlapping(Builder $query, string $from, string $until): Builder
    {
        return $query->whereDate('active_from', '<=', $until)->whereDate('active_until', '>=', $from);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        $today = today()->toDateString();

        return match ($status) {
            self::STATUS_ACTIVE => $query->activeOn(),
            self::STATUS_SCHEDULED => $query->whereDate('active_from', '>', $today),
            self::STATUS_EXPIRED => $query->whereDate('active_until', '<', $today),
            default => $query,
        };
    }
}
