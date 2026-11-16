<?php

namespace App\Http\Requests;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function rules(): array
    {
        $isTransfer = $this->input('type') === PaymentMethodType::Transfer->value;

        return [
            'name' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'bank_name' => [Rule::requiredIf($isTransfer), 'nullable', 'string', 'max:50'],
            'account_number' => [Rule::requiredIf($isTransfer), 'nullable', 'string', 'max:50'],
            'account_holder' => [Rule::requiredIf($isTransfer), 'nullable', 'string', 'max:100'],
            'qris_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(OrderChannel::values())],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return ['channels.required' => 'Pilih minimal satu kanal.', 'channels.min' => 'Pilih minimal satu kanal.'];
    }
}
