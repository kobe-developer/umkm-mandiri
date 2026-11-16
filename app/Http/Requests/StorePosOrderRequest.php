<?php

namespace App\Http\Requests;

use App\Enums\OrderChannel;
use App\Models\StoreSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePosOrderRequest extends FormRequest
{
    use ValidatesOrderItems;

    protected function prepareForValidation(): void
    {
        $this->normalizeWhatsapp();

        // Diskon manual hanya untuk owner, kecuali owner mengizinkan kasir
        if (! $this->canDiscount()) {
            $this->merge(['discount_type' => null, 'discount_value' => 0]);
        }

        // "Simpan dulu, bayar nanti" hanya untuk dine in
        if ($this->input('channel') !== OrderChannel::DineIn->value) {
            $this->merge(['pay_later' => false]);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->itemRules(),
            'channel' => ['required', Rule::in([OrderChannel::DineIn->value, OrderChannel::TakeAway->value])],
            'table_number' => ['nullable', 'string', 'max:10'],
            'customer_name' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^628\d{7,12}$/'],
            'notes' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['nullable', Rule::in(['nominal', 'percent'])],
            'discount_value' => ['nullable', 'integer', 'min:0', 'max:100000000', Rule::when($this->input('discount_type') === 'percent', 'max:100')],
            'pay_later' => ['boolean'],
            'payment_method_id' => ['exclude_if:pay_later,true', 'required', 'integer', 'exists:payment_methods,id'],
            'amount_received' => ['exclude_if:pay_later,true', 'nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->itemMessages(),
            'whatsapp_number.regex' => 'Nomor WhatsApp tidak valid.',
            'payment_method_id.required' => 'Pilih metode pembayaran.',
        ];
    }

    private function canDiscount(): bool
    {
        $user = $this->user();

        return $user->isOwner() || StoreSetting::current()->cashier_can_discount;
    }
}
