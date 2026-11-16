<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethodType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethodRequest;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\ActivityLogger;
use App\Services\ImageService;
use App\Support\BusinessRule;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/PaymentMethods', [
            'methods' => PaymentMethod::orderBy('sort_order')->orderBy('name')->get(),
            'types' => PaymentMethodType::options(),
            'channels' => array_map(fn (OrderChannel $c) => ['value' => $c->value, 'label' => $c->label()], OrderChannel::cases()),
        ]);
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $method = PaymentMethod::create([
            ...$this->attributes($data),
            'qris_image_path' => $request->hasFile('qris_image') ? $this->images->storeCompressed($request->file('qris_image'), 'qris') : null,
        ]);
        $this->logger->log('create', $method, null, $method->getAttributes(), "Tambah metode {$method->name}");

        return back()->with('success', "Metode {$method->name} ditambahkan.");
    }

    public function update(PaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $original = $paymentMethod->getAttributes();
        $attributes = $this->attributes($request->validated());

        if ($request->hasFile('qris_image')) {
            $this->images->delete($paymentMethod->qris_image_path);
            $attributes['qris_image_path'] = $this->images->storeCompressed($request->file('qris_image'), 'qris');
        }

        $paymentMethod->update($attributes);
        $this->logger->logChanges('update', $paymentMethod, $original, "Ubah metode {$paymentMethod->name}");

        return back()->with('success', "Metode {$paymentMethod->name} diperbarui.");
    }

    /** Metode yang sudah dipakai transaksi hanya bisa dinonaktifkan. */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $used = Order::where('payment_method_id', $paymentMethod->id)
            ->orWhere('planned_payment_method_id', $paymentMethod->id)->exists();
        BusinessRule::check(! $used, 'Metode sudah dipakai transaksi. Nonaktifkan saja.', 'method');

        $this->images->delete($paymentMethod->qris_image_path);
        $paymentMethod->delete();
        $this->logger->log('delete', $paymentMethod, ['name' => $paymentMethod->name], null, "Hapus metode {$paymentMethod->name}");

        return back()->with('success', "Metode {$paymentMethod->name} dihapus.");
    }

    private function attributes(array $data): array
    {
        $isTransfer = $data['type'] === PaymentMethodType::Transfer->value;

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'bank_name' => $isTransfer ? $data['bank_name'] : null,
            'account_number' => $isTransfer || $data['type'] === PaymentMethodType::Ewallet->value ? ($data['account_number'] ?? null) : null,
            'account_holder' => $isTransfer || $data['type'] === PaymentMethodType::Ewallet->value ? ($data['account_holder'] ?? null) : null,
            'channels' => array_values(array_unique($data['channels'])),
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
