<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'channel' => $this->channel->value,
            'channel_label' => $this->channel->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'next_status' => $this->status->next()?->value,
            'next_status_label' => $this->status->next()?->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'customer_name' => $this->customer_name,
            'whatsapp_number' => $this->whatsapp_number,
            'table_number' => $this->table_number,
            'fulfillment' => $this->fulfillment?->value,
            'fulfillment_label' => $this->fulfillment?->label(),
            'address' => $this->address,
            'notes' => $this->notes,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'total' => $this->total,
            'amount_received' => $this->amount_received,
            'change_amount' => $this->change_amount,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod?->only(['id', 'name', 'type'])),
            'planned_payment_method' => $this->whenLoaded('plannedPaymentMethod', fn () => $this->plannedPaymentMethod?->only(['id', 'name', 'type'])),
            'cashier' => $this->whenLoaded('cashier', fn () => $this->cashier?->only(['id', 'name'])),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'unit_price' => $item->unit_price,
                'original_price' => $item->original_price,
                'is_promo' => $item->promotion_id !== null || $item->promoSaving() > 0,
                'quantity' => $item->quantity,
                'notes' => $item->notes,
                'subtotal' => $item->subtotal,
            ])),
            'items_count' => $this->whenCounted('items'),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at,
            'confirmed_at' => $this->confirmed_at,
            'processing_at' => $this->processing_at,
            'ready_at' => $this->ready_at,
            'status_times' => $this->statusTimes(),
            'paid_at' => $this->paid_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'voided_at' => $this->voided_at,
        ];
    }
}
