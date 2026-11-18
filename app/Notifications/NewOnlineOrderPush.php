<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\Rupiah;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Web push ke owner & kasir saat order online baru masuk. */
class NewOnlineOrderPush extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    /** @return list<class-string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $order = $this->order->loadCount('items');
        $details = array_filter([
            $order->customer_name,
            Rupiah::format($order->total),
            $order->fulfillment?->label(),
            "{$order->items_count} item",
        ]);

        return PushMessage::make(
            title: "Order online baru · {$order->order_number}",
            body: implode(' · ', $details),
            url: route('pos.orders.show', $order, absolute: false),
            tag: "order-new-{$order->order_number}",
        )->requireInteraction();
    }
}
