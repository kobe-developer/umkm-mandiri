<?php

namespace App\Services;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\User;
use App\Support\BusinessRule;
use Illuminate\Support\Facades\DB;

/** Perubahan status order & pembayaran setelah order dibuat. */
class OrderLifecycleService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly OrderPricer $pricer,
        private readonly OwnerPinVerifier $pinVerifier,
        private readonly ActivityLogger $logger,
    ) {}

    /** Tandai lunas. Stok berkurang saat order Lunas. */
    public function pay(Order $order, int $paymentMethodId, ?int $amountReceived, User $cashier, Shift $shift): Order
    {
        return $this->transact($order, function (Order $order) use ($paymentMethodId, $amountReceived, $cashier, $shift) {
            BusinessRule::check($order->payment_status === PaymentStatus::Unpaid, 'Order ini sudah dibayar atau di-void.', 'payment');
            BusinessRule::check($order->status !== OrderStatus::Cancelled, 'Order yang dibatalkan tidak bisa dibayar.', 'payment');
            BusinessRule::check($order->status !== OrderStatus::New, 'Konfirmasi order online terlebih dahulu.', 'payment');

            $method = PaymentMethod::find($paymentMethodId);
            BusinessRule::check($method?->supportsChannel($order->channel) ?? false, 'Metode pembayaran tidak tersedia untuk kanal ini.', 'payment_method_id');

            $change = null;
            if ($method->isCash()) {
                BusinessRule::check($amountReceived !== null && $amountReceived >= $order->total, 'Uang diterima kurang dari total.', 'amount_received');
                $change = $amountReceived - $order->total;
            }

            $order->fill([
                'payment_status' => PaymentStatus::Paid,
                'payment_method_id' => $method->id,
                'amount_received' => $method->isCash() ? $amountReceived : null,
                'change_amount' => $change,
                'paid_at' => now(),
                'shift_id' => $shift->id,
                'cashier_id' => $order->cashier_id ?? $cashier->id,
            ])->save();

            $this->stock->deductForOrder($order, $cashier);
        });
    }

    /** Maju satu langkah: Baru → Dikonfirmasi → Diproses → Siap → Selesai. */
    public function advance(Order $order, User $user): Order
    {
        return $this->transact($order, function (Order $order) use ($user) {
            $next = $order->status->next();
            BusinessRule::check($next !== null, 'Status order sudah final.', 'status');

            if ($next === OrderStatus::Completed) {
                $this->assertCanComplete($order);
            }

            $this->applyStatus($order, $next, $user);
        });
    }

    /** Langsung Selesai (untuk order yang sudah diproses dan lunas). */
    public function complete(Order $order, User $user): Order
    {
        return $this->transact($order, function (Order $order) use ($user) {
            BusinessRule::check(
                in_array($order->status, [OrderStatus::Processing, OrderStatus::Ready], true),
                'Hanya order yang sedang diproses/siap yang bisa diselesaikan.',
                'status',
            );
            $this->assertCanComplete($order);
            $this->applyStatus($order, OrderStatus::Completed, $user);
        });
    }

    /** Batalkan order yang belum dibayar. Order lunas harus di-void. */
    public function cancel(Order $order, string $reason, User $user): Order
    {
        return $this->transact($order, function (Order $order) use ($reason, $user) {
            BusinessRule::check(! $order->status->isFinal(), 'Order sudah selesai atau dibatalkan.', 'status');
            BusinessRule::check(! $order->isPaid(), 'Order sudah lunas, gunakan Void.', 'status');

            $order->fill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ])->save();

            $this->stock->restoreForOrder($order, $user, "Batal {$order->order_number}: {$reason}");
        });
    }

    /** Void/refund order lunas. Kasir wajib PIN owner. */
    public function void(Order $order, string $reason, User $user, ?string $pin): Order
    {
        $approver = $user->isOwner() ? $user : $this->pinVerifier->verify($pin, $user);

        $order = $this->transact($order, function (Order $order) use ($reason, $user, $approver) {
            BusinessRule::check($order->isPaid(), 'Hanya order lunas yang bisa di-void.', 'status');

            $order->fill([
                'payment_status' => PaymentStatus::Void,
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_approved_by' => $approver->id,
                'void_reason' => $reason,
            ])->save();

            $this->stock->restoreForOrder($order, $user, "Void {$order->order_number}: {$reason}");
        });

        $this->logger->log('void', $order, null, ['total' => $order->total, 'approved_by' => $approver->name], "Void {$order->order_number}: {$reason}");

        return $order;
    }

    /**
     * Ubah item order online sebelum dikonfirmasi (misal ada yang habis).
     *
     * @param  list<array{product_id: int, quantity: int, notes?: ?string}>  $items
     */
    public function updateItems(Order $order, array $items, User $user): Order
    {
        return $this->transact($order, function (Order $order) use ($items) {
            BusinessRule::check($order->channel === OrderChannel::Online && $order->status === OrderStatus::New, 'Item hanya bisa diubah pada order online yang belum dikonfirmasi.', 'items');

            $priced = $this->pricer->price($items, OrderChannel::Online);
            $order->items()->delete();
            $order->items()->createMany($priced['lines']);
            $order->fill(['subtotal' => $priced['subtotal'], 'discount' => 0, 'total' => $priced['subtotal']])->save();
        });
    }

    private function assertCanComplete(Order $order): void
    {
        BusinessRule::check($order->isPaid(), 'Order belum lunas. Tandai pembayaran terlebih dahulu.', 'payment');
    }

    private function applyStatus(Order $order, OrderStatus $status, User $user): void
    {
        $timestamps = match ($status) {
            OrderStatus::Confirmed => ['confirmed_at' => now(), 'cashier_id' => $order->cashier_id ?? $user->id],
            OrderStatus::Processing => ['processing_at' => now()],
            OrderStatus::Ready => ['ready_at' => now()],
            OrderStatus::Completed => ['completed_at' => now()],
            default => [],
        };

        $order->fill(['status' => $status, ...$timestamps])->save();
    }

    /** Jalankan perubahan dengan baris order terkunci, lalu broadcast. */
    private function transact(Order $order, callable $callback): Order
    {
        $fresh = DB::transaction(function () use ($order, $callback) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $callback($locked);

            return $locked;
        });

        rescue(fn () => OrderUpdated::dispatch($fresh));

        return $fresh->fresh(['items', 'paymentMethod']);
    }
}
