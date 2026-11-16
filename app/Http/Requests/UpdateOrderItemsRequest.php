<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemsRequest extends FormRequest
{
    use ValidatesOrderItems;

    public function rules(): array
    {
        return $this->itemRules();
    }

    public function messages(): array
    {
        return $this->itemMessages();
    }
}
