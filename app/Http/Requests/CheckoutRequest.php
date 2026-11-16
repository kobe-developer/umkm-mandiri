<?php

namespace App\Http\Requests;

use App\Enums\Fulfillment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    use ValidatesOrderItems;

    protected function prepareForValidation(): void
    {
        $this->merge(['customer_name' => trim((string) $this->input('customer_name'))]);
        $this->normalizeWhatsapp();
    }

    public function rules(): array
    {
        return [
            ...$this->itemRules(),
            'customer_name' => ['required', 'string', 'min:2', 'max:50'],
            'whatsapp_number' => ['required', 'string', 'regex:/^628\d{7,12}$/'],
            'fulfillment' => ['required', Rule::enum(Fulfillment::class)],
            'address' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:255'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->itemMessages(),
            'customer_name.required' => 'Nama wajib diisi.',
            'customer_name.min' => 'Nama minimal 2 karakter.',
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx / 62xx / +62xx.',
            'address.required_if' => 'Alamat/patokan wajib diisi untuk pesanan diantar.',
            'payment_method_id.required' => 'Pilih rencana metode bayar.',
            'consent.accepted' => 'Setujui penggunaan nomor WA untuk konfirmasi pesanan.',
        ];
    }
}
