<?php

namespace App\Support;

use App\Models\Order;

/** Membangun link wa.me berisi pesan konfirmasi order (Fase 1, manual). */
final class WhatsAppLink
{
    public static function forOrderConfirmation(Order $order, string $storeName, ?string $statusUrl = null): ?string
    {
        if (! $order->whatsapp_number) {
            return null;
        }

        return 'https://wa.me/'.$order->whatsapp_number.'?text='.rawurlencode(self::confirmationMessage($order, $storeName, $statusUrl));
    }

    public static function confirmationMessage(Order $order, string $storeName, ?string $statusUrl = null): string
    {
        $lines = [
            "Halo {$order->customer_name}, terima kasih sudah pesan di {$storeName}.",
            '',
            "Pesanan *{$order->order_number}*:",
        ];

        foreach ($order->items as $item) {
            $line = "- {$item->product_name} x{$item->quantity} = ".Rupiah::format($item->subtotal);
            $lines[] = $item->notes ? "{$line} ({$item->notes})" : $line;
        }

        if ($order->discount > 0) {
            $lines[] = 'Diskon: -'.Rupiah::format($order->discount);
        }

        $lines[] = '*Total: '.Rupiah::format($order->total).'*';
        $lines[] = 'Pengambilan: '.($order->fulfillment?->label() ?? '-');

        if ($order->address) {
            $lines[] = "Alamat: {$order->address}";
        }

        if ($order->plannedPaymentMethod) {
            $lines[] = "Pembayaran: {$order->plannedPaymentMethod->name}";
        }

        $lines[] = '';
        $lines[] = 'Pesanan kamu sudah kami konfirmasi dan segera diproses ya.';

        if ($statusUrl) {
            $lines[] = "Cek status: {$statusUrl}";
        }

        return implode("\n", $lines);
    }
}
