<?php

namespace App\Http\Requests;

use App\Enums\OrderChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => [Rule::in(OrderChannel::values())],
            'active_from' => ['required', 'date_format:Y-m-d'],
            // Promo baru tidak boleh langsung kedaluwarsa; promo lama boleh diubah ke tanggal lampau (mengakhiri lebih awal).
            'active_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:active_from', ...($isCreate ? ['after_or_equal:today'] : [])],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Pilih produk.',
            'product_id.exists' => 'Produk tidak ditemukan.',
            'price.required' => 'Harga promo wajib diisi.',
            'price.min' => 'Harga promo minimal Rp1.',
            'channels.required' => 'Pilih minimal satu kanal.',
            'channels.min' => 'Pilih minimal satu kanal.',
            'active_from.required' => 'Tanggal mulai wajib diisi.',
            'active_until.required' => 'Tanggal berakhir wajib diisi.',
            'active_until.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai atau hari ini.',
        ];
    }
}
