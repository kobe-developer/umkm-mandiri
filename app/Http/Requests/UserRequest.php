<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['boolean'],
            'password' => [$user ? 'nullable' : 'required', 'string', Password::min(8)],
        ];
    }
}
