<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photo' => ['boolean'],
            'is_active' => ['boolean'],
            'show_online' => ['boolean'],
            'track_stock' => ['boolean'],
            'stock_minimum' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'initial_stock' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_available' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function attributes(): array
    {
        return ['category_id' => 'kategori', 'name' => 'nama', 'price' => 'harga', 'photo' => 'foto'];
    }
}
