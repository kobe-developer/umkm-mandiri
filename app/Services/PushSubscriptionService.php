<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use NotificationChannels\WebPush\PushSubscription;

/** Menyimpan/menghapus langganan web push milik staf (User) dan pelanggan (Order). */
class PushSubscriptionService
{
    /**
     * Satu perangkat staf hanya milik satu akun: bila tablet kasir dipakai bergantian,
     * langganan pindah ke akun yang terakhir mengaktifkan agar notifikasi tidak dobel.
     *
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}, content_encoding?: ?string}  $data
     */
    public function subscribeUser(User $user, array $data): PushSubscription
    {
        return DB::transaction(function () use ($user, $data) {
            PushSubscription::query()
                ->where('subscribable_type', $user->getMorphClass())
                ->where('subscribable_id', '!=', $user->getKey())
                ->where('endpoint', $data['endpoint'])
                ->delete();

            return $this->upsert($user, $data);
        });
    }

    public function unsubscribeUser(User $user, string $endpoint): void
    {
        $user->deletePushSubscription($endpoint);
    }

    /**
     * Perangkat pelanggan bisa mengikuti beberapa pesanan sekaligus (endpoint sama, order beda).
     *
     * @param  array{endpoint: string, keys: array{p256dh: string, auth: string}, content_encoding?: ?string}  $data
     */
    public function subscribeOrder(Order $order, array $data): ?PushSubscription
    {
        if (! $order->isOnline() || $order->status->isFinal()) {
            return null;
        }

        $alreadySubscribed = $order->pushSubscriptions()->where('endpoint', $data['endpoint'])->exists();
        if (! $alreadySubscribed && $order->pushSubscriptions()->count() >= config('store.push_max_devices_per_order')) {
            return null;
        }

        return $this->upsert($order, $data);
    }

    /** Hapus langganan order yang sudah selesai/dibatalkan lebih dari N hari. */
    public function pruneFinishedOrders(): int
    {
        $cutoff = now()->subDays(config('store.push_order_retention_days'));

        return PushSubscription::query()
            ->where('subscribable_type', (new Order)->getMorphClass())
            ->whereIn('subscribable_id', Order::query()
                ->select('id')
                ->whereIn('status', [OrderStatus::Completed->value, OrderStatus::Cancelled->value])
                ->where('updated_at', '<', $cutoff))
            ->delete();
    }

    /** @param  array<string, mixed>  $data */
    private function upsert(Model $owner, array $data): PushSubscription
    {
        /** @var PushSubscription */
        return $owner->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['content_encoding'] ?? 'aes128gcm',
            ],
        );
    }
}
