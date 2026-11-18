<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOnlineOrderPush;
use App\Notifications\OrderStatusPush;
use Illuminate\Support\Facades\Notification;

/**
 * Memutuskan kapan web push dikirim. Pengiriman sendiri lewat antrean, sehingga
 * kegagalan layanan push tidak pernah menggagalkan transaksi.
 */
class OrderPushNotifier
{
    /** Status yang dikabarkan ke pelanggan. */
    private const CUSTOMER_STATUSES = [
        OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Ready,
        OrderStatus::Completed, OrderStatus::Cancelled,
    ];

    public static function isConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
    }

    /** Order online baru → semua owner & kasir aktif yang mengaktifkan notifikasi. */
    public function newOnlineOrder(Order $order): void
    {
        if (! self::isConfigured() || ! $order->isOnline()) {
            return;
        }

        $staff = User::where('is_active', true)->whereHas('pushSubscriptions')->get();
        if ($staff->isNotEmpty()) {
            Notification::send($staff, new NewOnlineOrderPush($order));
        }
    }

    /** Status/pembayaran order online berubah → perangkat pelanggan yang berlangganan. */
    public function orderChanged(Order $order, OrderStatus $previousStatus, PaymentStatus $previousPayment): void
    {
        if (! self::isConfigured() || ! $order->isOnline()) {
            return;
        }

        $event = $this->eventFor($order, $previousStatus, $previousPayment);
        if ($event !== null && $order->pushSubscriptions()->exists()) {
            $order->notify(new OrderStatusPush($order, $event));
        }
    }

    private function eventFor(Order $order, OrderStatus $previousStatus, PaymentStatus $previousPayment): ?string
    {
        // Perubahan status lebih penting (mis. void = dibatalkan sekaligus pembayaran void)
        if ($order->status !== $previousStatus && in_array($order->status, self::CUSTOMER_STATUSES, true)) {
            return $order->status->value;
        }

        if ($previousPayment !== PaymentStatus::Paid && $order->payment_status === PaymentStatus::Paid) {
            return OrderStatusPush::EVENT_PAID;
        }

        return null;
    }
}
