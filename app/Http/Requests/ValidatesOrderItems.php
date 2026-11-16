<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;

/** Aturan item order yang dipakai bersama (POS, checkout, ubah item). */
trait ValidatesOrderItems
{
    protected function itemRules(): array
    {
        $max = config('store.max_item_quantity');

        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct:strict', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', "max:{$max}"],
            'items.*.notes' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function itemMessages(): array
    {
        return [
            'items.required' => 'Keranjang masih kosong.',
            'items.min' => 'Keranjang masih kosong.',
            'items.*.product_id.distinct' => 'Produk yang sama ditulis dua kali; gabungkan qty-nya.',
            'items.*.quantity.max' => 'Qty per item maksimal :max.',
        ];
    }

    /** Ubah nomor WA ke format 62… sebelum validasi. */
    protected function normalizeWhatsapp(): void
    {
        if ($this->filled('whatsapp_number')) {
            $this->merge(['whatsapp_number' => PhoneNumber::normalize($this->input('whatsapp_number')) ?? $this->input('whatsapp_number')]);
        }
    }
}
