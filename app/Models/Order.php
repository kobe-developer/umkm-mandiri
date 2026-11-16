<?php

namespace App\Models;

use App\Enums\Fulfillment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'channel', 'status', 'payment_status', 'customer_id', 'customer_name',
        'whatsapp_number', 'table_number', 'fulfillment', 'address', 'notes', 'subtotal',
        'discount_type', 'discount_value', 'discount', 'total', 'payment_method_id',
        'planned_payment_method_id', 'amount_received', 'change_amount', 'cashier_id', 'shift_id',
        'public_token', 'stock_deducted', 'confirmed_at', 'processing_at', 'ready_at', 'paid_at', 'completed_at', 'cancelled_at',
        'cancel_reason', 'voided_at', 'voided_by', 'void_approved_by', 'void_reason', 'ip_address',
    ];

    protected $hidden = ['public_token', 'ip_address'];

    protected function casts(): array
    {
        return [
            'channel' => OrderChannel::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'fulfillment' => Fulfillment::class,
            'subtotal' => 'integer',
            'discount_value' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'amount_received' => 'integer',
            'change_amount' => 'integer',
            'stock_deducted' => 'boolean',
            'confirmed_at' => 'datetime',
            'processing_at' => 'datetime',
            'ready_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function plannedPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'planned_payment_method_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function isOnline(): bool
    {
        return $this->channel === OrderChannel::Online;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * Waktu order masuk tiap status (null = belum dicapai), dikunci dengan nilai status.
     *
     * @return array<string, ?Carbon>
     */
    public function statusTimes(): array
    {
        return [
            OrderStatus::New->value => $this->created_at,
            OrderStatus::Confirmed->value => $this->confirmed_at,
            OrderStatus::Processing->value => $this->processing_at,
            OrderStatus::Ready->value => $this->ready_at,
            OrderStatus::Completed->value => $this->completed_at,
            OrderStatus::Cancelled->value => $this->cancelled_at,
        ];
    }

    /** Channel WebSocket publik untuk pelanggan; nama memuat token rahasia agar tidak bisa ditebak. */
    public function trackingChannel(): ?string
    {
        return $this->public_token ? "order.{$this->public_token}" : null;
    }

    /** URL status publik (nomor order + token). */
    public function statusUrl(): ?string
    {
        return $this->public_token
            ? route('menu.order.show', ['order' => $this->order_number, 't' => $this->public_token])
            : null;
    }

    /** Hanya order Selesai + Lunas yang dihitung sebagai omzet. */
    public function scopeRevenue(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Completed->value)
            ->where('payment_status', PaymentStatus::Paid->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [OrderStatus::Completed->value, OrderStatus::Cancelled->value]);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Unpaid->value)
            ->where('status', '!=', OrderStatus::Cancelled->value);
    }
}
