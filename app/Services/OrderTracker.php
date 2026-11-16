<?php

namespace App\Services;

use App\Enums\OrderChannel;
use App\Models\Order;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Daftar order online milik pengunjung menu (tanpa login), disimpan di cookie terenkripsi
 * agar riwayat bertahan di perangkat yang sama. Order masuk daftar saat checkout atau saat
 * link status dibuka dengan token yang benar.
 */
class OrderTracker
{
    public const COOKIE = 'pesanan_saya';

    private const MAX_TRACKED = 20;

    private const COOKIE_MINUTES = 60 * 24 * 90;

    public function __construct(
        private readonly Request $request,
        private readonly CookieJar $cookies,
    ) {}

    public function remember(Order $order): void
    {
        $ids = collect($this->trackedIds())
            ->prepend($order->id)
            ->unique()
            ->take(self::MAX_TRACKED)
            ->values()
            ->all();

        $this->cookies->queue(self::COOKIE, json_encode($ids), self::COOKIE_MINUTES);
    }

    /** @return list<array<string, mixed>> order yang masih berjalan (sebelum Selesai/Dibatalkan) */
    public function active(): array
    {
        return $this->orders()
            ->reject(fn (Order $order) => $order->status->isFinal())
            ->map(fn (Order $order) => $this->summary($order))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> semua order pengunjung ini, terbaru dulu */
    public function history(): array
    {
        return $this->orders()->load('items')
            ->map(fn (Order $order) => [
                ...$this->summary($order),
                'created_at' => $order->created_at,
                'items' => $order->items->map(fn ($item) => ['name' => $item->product_name, 'quantity' => $item->quantity])->all(),
            ])
            ->all();
    }

    public function hasHistory(): bool
    {
        return $this->trackedIds() !== [];
    }

    /** @return Collection<int, Order> */
    private function orders(): Collection
    {
        $ids = $this->trackedIds();
        if ($ids === []) {
            return new Collection;
        }

        return Order::query()
            ->whereIn('id', $ids)
            ->where('channel', OrderChannel::Online->value)
            ->withCount('items')
            ->latest('id')
            ->get();
    }

    /** @return array<string, mixed> */
    private function summary(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'status_time' => $order->statusTimes()[$order->status->value] ?? $order->updated_at,
            'is_final' => $order->status->isFinal(),
            'fulfillment' => $order->fulfillment?->value,
            'total' => $order->total,
            'items_count' => $order->items_count,
            'status_url' => $order->statusUrl(),
            'realtime_channel' => $order->trackingChannel(),
        ];
    }

    /**
     * Isi cookie tidak dipercaya begitu saja: cookie terenkripsi Laravel, dan nilai yang
     * gagal didekripsi (diubah pengguna) sudah dibuang middleware menjadi null.
     *
     * @return list<int>
     */
    private function trackedIds(): array
    {
        $decoded = json_decode((string) $this->request->cookie(self::COOKIE, ''), true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($id) => is_int($id) && $id > 0)
            ->take(self::MAX_TRACKED)
            ->values()
            ->all();
    }
}
