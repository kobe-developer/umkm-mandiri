<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1f2937; }
        h1 { font-size: 14px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        .summary { margin: 8px 0 12px; }
        .summary span { display: inline-block; margin-right: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 3px 4px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .num { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ $store->store_name }} — Laporan Transaksi</h1>
    <div class="muted">Periode {{ $period->start->format('d/m/Y') }} – {{ $period->end->format('d/m/Y') }} · Dicetak {{ now()->format('d/m/Y H:i') }}</div>
    <div class="summary">
        <span><strong>Omzet (Selesai + Lunas, sesuai filter):</strong> {{ \App\Support\Rupiah::format($summary['revenue']) }}</span>
        <span><strong>Transaksi:</strong> {{ $summary['transactions'] }}</span>
        <span><strong>Rata-rata:</strong> {{ \App\Support\Rupiah::format($summary['average']) }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>No. Order</th><th>Tanggal</th><th>Kanal</th><th>Status</th><th>Bayar</th><th>Metode</th>
                <th>Pelanggan</th><th>Item</th><th class="num">Diskon</th><th class="num">Total</th><th>Kasir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $order->channel->label() }}</td>
                    <td>{{ $order->status->label() }}</td>
                    <td>{{ $order->payment_status->label() }}</td>
                    <td>{{ $order->paymentMethod?->name }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->items->map(fn ($i) => "{$i->product_name} x{$i->quantity}")->implode(', ') }}</td>
                    <td class="num">{{ \App\Support\Rupiah::format($order->discount) }}</td>
                    <td class="num">{{ \App\Support\Rupiah::format($order->total) }}</td>
                    <td>{{ $order->cashier?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="muted">Tidak ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($orders->count() >= $limit)
        <p class="muted">Ditampilkan {{ $limit }} transaksi pertama. Gunakan export Excel untuk data lengkap.</p>
    @endif
</body>
</html>
