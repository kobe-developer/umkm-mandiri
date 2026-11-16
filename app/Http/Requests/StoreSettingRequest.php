<?php

namespace App\Http\Requests;

use App\Models\StoreSetting;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('whatsapp_number')) {
            $this->merge(['whatsapp_number' => PhoneNumber::normalize($this->input('whatsapp_number')) ?? $this->input('whatsapp_number')]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'store_name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^628\d{7,12}$/'],
            'delivery_note' => ['nullable', 'string', 'max:255'],
            'receipt_note' => ['nullable', 'string', 'max:255'],
            'auto_schedule' => ['boolean'],
            'online_order_enabled' => ['boolean'],
            'min_order' => ['required', 'integer', 'min:0', 'max:10000000'],
            'allow_pickup' => ['boolean'],
            'allow_delivery' => ['boolean'],
            'cashier_can_discount' => ['boolean'],
            'cashier_can_view_revenue' => ['boolean'],
            'operating_hours' => ['required', 'array'],
        ];

        foreach (StoreSetting::DAYS as $day) {
            $rules["operating_hours.{$day}.open"] = ['required', 'date_format:H:i'];
            $rules["operating_hours.{$day}.close"] = ['required', 'date_format:H:i'];
            $rules["operating_hours.{$day}.closed"] = ['boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['whatsapp_number.regex' => 'Nomor WhatsApp toko tidak valid.'];
    }
}
