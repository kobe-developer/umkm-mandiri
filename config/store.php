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

    // Web push: hanya endpoint HTTPS milik layanan push browser yang diterima (mencegah SSRF,
    // karena endpoint dikirim oleh pelanggan anonim lalu server melakukan request ke sana).
    'push_endpoint_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'PUSH_ENDPOINT_HOSTS',
        'fcm.googleapis.com,push.services.mozilla.com,push.apple.com,notify.windows.com',
    ))))),

    // Maksimal perangkat yang bisa berlangganan notifikasi untuk satu order online
    'push_max_devices_per_order' => 3,

    // Langganan push milik order yang sudah final dihapus setelah sekian hari
    'push_order_retention_days' => 2,
];
