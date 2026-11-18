<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushSubscriptionRequest extends FormRequest
{
    use ValidatesPushSubscription;

    public function rules(): array
    {
        return $this->pushSubscriptionRules();
    }
}
