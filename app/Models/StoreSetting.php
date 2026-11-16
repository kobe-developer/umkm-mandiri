<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    protected $fillable = [
        'store_name', 'address', 'whatsapp_number', 'delivery_note', 'receipt_note',
        'is_open', 'auto_schedule', 'operating_hours', 'last_schedule_state',
        'online_order_enabled', 'min_order', 'allow_pickup', 'allow_delivery',
        'cashier_can_discount', 'cashier_can_view_revenue',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'auto_schedule' => 'boolean',
            'operating_hours' => 'array',
            'last_schedule_state' => 'boolean',
            'online_order_enabled' => 'boolean',
            'min_order' => 'integer',
            'allow_pickup' => 'boolean',
            'allow_delivery' => 'boolean',
            'cashier_can_discount' => 'boolean',
            'cashier_can_view_revenue' => 'boolean',
        ];
    }

    /** Toko ini single store: selalu satu baris. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        $hours = [];
        foreach (self::DAYS as $day) {
            $hours[$day] = ['open' => '10:00', 'close' => '22:00', 'closed' => false];
        }

        return [
            'store_name' => config('app.name'),
            'is_open' => false,
            'auto_schedule' => true,
            'operating_hours' => $hours,
            'online_order_enabled' => true,
            'min_order' => 0,
            'allow_pickup' => true,
            'allow_delivery' => true,
            'delivery_note' => 'Free delivery sekitaran kampus',
        ];
    }

    public function acceptsOnlineOrders(): bool
    {
        return $this->is_open && $this->online_order_enabled;
    }
}
