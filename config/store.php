<?php

return [
    // Prefix nomor order, misal ORD-20261116-0012
    'order_prefix' => env('ORDER_PREFIX', 'ORD'),

    // Anti-spam: maksimal order "Baru" yang belum dikonfirmasi per nomor WA
    'max_pending_orders_per_whatsapp' => (int) env('MAX_PENDING_ORDERS_PER_WA', 3),

    // Rate limit checkout per IP (jumlah per menit)
    'checkout_rate_limit_per_minute' => (int) env('CHECKOUT_RATE_LIMIT', 5),

    // Percobaan PIN owner: maksimal per kasir dan total semua kasir, dalam jendela (detik)
    'pin_max_attempts' => (int) env('PIN_MAX_ATTEMPTS', 5),
    'pin_max_attempts_global' => (int) env('PIN_MAX_ATTEMPTS_GLOBAL', 15),
    'pin_lockout_seconds' => (int) env('PIN_LOCKOUT_SECONDS', 1800),

    // Interval polling (detik) sebagai cadangan WebSocket
    'poll_interval_seconds' => (int) env('POLL_INTERVAL', 10),

    // Ukuran maksimum foto produk setelah dikompres (px, sisi terpanjang)
    'photo_max_dimension' => 800,
    'photo_quality' => 75,

    // Maksimum qty per item dalam satu order
    'max_item_quantity' => 50,
];
