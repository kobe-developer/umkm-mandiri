<?php

namespace App\Notifications;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Rupiah;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** Web push ke pelanggan (perangkat yang berlangganan saat checkout) saat status/pembayaran berubah. */
class OrderStatusPush extends Notification implements ShouldQueue
{
    use Queueable;

    public const EVENT_PAID = 'lunas';

    /** @param  string  $event  nilai OrderStatus, atau EVENT_PAID untuk pembayaran lunas */
    public function __construct(public Order $order, public string $event)
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
        [$title, $body] = $this->content();
        $number = $this->order->order_number;

        return PushMessage::make(
            title: $title,
            body: "{$number} · {$body}",
            url: route('menu.order.show', ['order' => $number, 't' => $this->order->public_token], absolute: false),
            tag: "order-{$number}",
        );
    }

    /** @return array{0: string, 1: string} */
    private function content(): array
    {
        if ($this->event === self::EVENT_PAID) {
            return ['Pembayaran diterima', 'Pembayaran '.Rupiah::format($this->order->total).' sudah lunas. Terima kasih!'];
        }

        $isDelivery = $this->order->fulfillment === Fulfillment::Delivery;

        return match (OrderStatus::from($this->event)) {
            OrderStatus::Confirmed => ['Pesanan dikonfirmasi', 'Pesananmu sudah diterima toko.'],
            OrderStatus::Processing => ['Pesanan sedang dibuat', 'Pesananmu sedang disiapkan.'],
            OrderStatus::Ready => $isDelivery
                ? ['Pesanan sedang diantar', 'Pesananmu dalam perjalanan.']
                : ['Pesanan siap diambil', 'Silakan ambil pesananmu di toko.'],
            OrderStatus::Completed => ['Pesanan selesai', 'Terima kasih sudah memesan!'],
            OrderStatus::Cancelled => ['Pesanan dibatalkan', $this->order->cancel_reason ? "Alasan: {$this->order->cancel_reason}" : 'Pesananmu dibatalkan oleh toko.'],
            OrderStatus::New => ['Pesanan diterima', 'Menunggu konfirmasi toko.'],
        };
    }
}
