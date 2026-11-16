<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Perubahan status/pembayaran order → layar kasir dan halaman pelanggan diperbarui.
 * Order online juga dikirim ke channel publik ber-token milik pelanggan (tanpa login).
 */
class OrderUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        $customerChannel = $this->order->trackingChannel();

        return array_values(array_filter([
            new PrivateChannel('orders'),
            $customerChannel ? new Channel($customerChannel) : null,
        ]));
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_number' => $this->order->order_number,
            'status' => $this->order->status->value,
            'payment_status' => $this->order->payment_status->value,
        ];
    }
}
