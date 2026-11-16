<?php

namespace App\Services;

use App\Enums\Fulfillment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderCreated;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\BusinessRule;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderCreationService
{
    public function __construct(
        private readonly OrderPricer $pricer,
        private readonly OrderNumberGenerator $numbers,
        private readonly CustomerService $customers,
        private readonly OrderLifecycleService $lifecycle,
    ) {}

    /**
     * Order dine in / take away dari kasir: langsung berstatus Diproses.
     *
     * @param  array<string, mixed>  $data  data tervalidasi dari StorePosOrderRequest
     */
    public function createPosOrder(array $data, User $cashier, Shift $shift): Order
    {
        $channel = OrderChannel::from($data['channel']);
        BusinessRule::check($channel !== OrderChannel::Online, 'Kanal tidak valid untuk POS.', 'channel');

        $order = DB::transaction(function () use ($data, $cashier, $shift, $channel) {
            $priced = $this->pricer->price($data['items'], $channel);
            $discount = $this->pricer->discount($priced['subtotal'], $data['discount_type'] ?? null, (int) ($data['discount_value'] ?? 0));
            $customer = ! empty($data['whatsapp_number'])
                ? $this->customers->recordOrder($data['customer_name'] ?: 'Pelanggan', $data['whatsapp_number'])
                : null;

            $order = Order::create([
                'order_number' => $this->numbers->next(now()),
                'channel' => $channel,
                'status' => OrderStatus::Processing,
                'payment_status' => PaymentStatus::Unpaid,
                'customer_id' => $customer?->id,
                'customer_name' => $data['customer_name'] ?? null,
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'table_number' => $channel === OrderChannel::DineIn ? ($data['table_number'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $priced['subtotal'],
                'discount_type' => $discount > 0 ? $data['discount_type'] : null,
                'discount_value' => $discount > 0 ? (int) $data['discount_value'] : 0,
                'discount' => $discount,
                'total' => $priced['subtotal'] - $discount,
                'cashier_id' => $cashier->id,
                'shift_id' => $shift->id,
                'confirmed_at' => now(),
                'processing_at' => now(),
            ]);
            $order->items()->createMany($priced['lines']);

            if (empty($data['pay_later'])) {
                $this->lifecycle->pay($order, (int) $data['payment_method_id'], $data['amount_received'] ?? null, $cashier, $shift);
            }

            return $order;
        });

        return $order->fresh(['items', 'paymentMethod']);
    }

    /**
     * Order dari menu online publik: berstatus Baru, menunggu konfirmasi kasir.
     *
     * @param  array<string, mixed>  $data  data tervalidasi dari CheckoutRequest
     */
    public function createOnlineOrder(array $data, ?string $ipAddress): Order
    {
        $settings = StoreSetting::current();
        $this->assertStoreAcceptsOrder($settings, Fulfillment::from($data['fulfillment']));

        $method = PaymentMethod::find($data['payment_method_id']);
        BusinessRule::check($method?->supportsChannel(OrderChannel::Online) ?? false, 'Metode pembayaran tidak tersedia untuk order online.', 'payment_method_id');

        $order = DB::transaction(function () use ($data, $settings, $ipAddress) {
            $priced = $this->pricer->price($data['items'], OrderChannel::Online);
            BusinessRule::check(
                $priced['subtotal'] >= $settings->min_order,
                'Minimal order '.Rupiah::format($settings->min_order).'.',
                'items',
            );

            // Kunci baris pelanggan dulu agar cek anti-spam tidak bisa dilewati request paralel
            $this->customers->lockByWhatsapp($data['whatsapp_number'], $data['customer_name']);
            $this->assertNotSpamming($data['whatsapp_number']);
            $customer = $this->customers->recordOrder($data['customer_name'], $data['whatsapp_number']);
            $fulfillment = Fulfillment::from($data['fulfillment']);

            $order = Order::create([
                'order_number' => $this->numbers->next(now()),
                'channel' => OrderChannel::Online,
                'status' => OrderStatus::New,
                'payment_status' => PaymentStatus::Unpaid,
                'customer_id' => $customer->id,
                'customer_name' => $data['customer_name'],
                'whatsapp_number' => $data['whatsapp_number'],
                'fulfillment' => $fulfillment,
                'address' => $fulfillment === Fulfillment::Delivery ? $data['address'] : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $priced['subtotal'],
                'discount' => 0,
                'total' => $priced['subtotal'],
                'planned_payment_method_id' => $data['payment_method_id'],
                'public_token' => Str::random(40),
                'ip_address' => $ipAddress,
            ]);
            $order->items()->createMany($priced['lines']);

            return $order;
        });

        // Broadcast langsung (bukan antrean) agar kasir menerima order seketika;
        // bila Reverb mati, order tetap tersimpan dan kasir memakai polling cadangan.
        rescue(fn () => OrderCreated::dispatch($order));

        return $order;
    }

    private function assertStoreAcceptsOrder(StoreSetting $settings, Fulfillment $fulfillment): void
    {
        BusinessRule::check($settings->is_open, 'Toko sedang tutup.', 'store');
        BusinessRule::check($settings->online_order_enabled, 'Order online sedang tidak aktif.', 'store');
        BusinessRule::check(
            $fulfillment === Fulfillment::Pickup ? $settings->allow_pickup : $settings->allow_delivery,
            "Opsi {$fulfillment->label()} sedang tidak tersedia.",
            'fulfillment',
        );
    }

    private function assertNotSpamming(string $whatsappNumber): void
    {
        $max = config('store.max_pending_orders_per_whatsapp');
        $pending = Order::where('whatsapp_number', $whatsappNumber)->where('status', OrderStatus::New->value)->count();

        BusinessRule::check(
            $pending < $max,
            "Masih ada {$pending} pesanan yang menunggu konfirmasi untuk nomor ini. Mohon tunggu konfirmasi kasir.",
            'whatsapp_number',
        );
    }
}
