<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'No. Order', 'Tanggal', 'Kanal', 'Status', 'Pembayaran', 'Metode', 'Pelanggan', 'No. WA',
            'Meja', 'Item', 'Subtotal', 'Diskon', 'Total', 'Kasir', 'Alasan Batal/Void',
        ];
    }

    public function map(mixed $row): array
    {
        /** @var Order $row */
        return array_map(fn ($value) => self::safe($value), [
            $row->order_number,
            $row->created_at->format('Y-m-d H:i'),
            $row->channel->label(),
            $row->status->label(),
            $row->payment_status->label(),
            $row->paymentMethod?->name,
            $row->customer_name,
            $row->whatsapp_number,
            $row->table_number,
            $row->items->map(fn ($i) => "{$i->product_name} x{$i->quantity}")->implode(', '),
            $row->subtotal,
            $row->discount,
            $row->total,
            $row->cashier?->name,
            $row->cancel_reason,
        ]);
    }

    /** Cegah formula injection: teks yang diawali = + - @ dijadikan teks biasa. */
    private static function safe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
