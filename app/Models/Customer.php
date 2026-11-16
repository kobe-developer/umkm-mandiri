<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'whatsapp_number', 'total_orders', 'last_order_at'];

    protected function casts(): array
    {
        return ['last_order_at' => 'datetime', 'total_orders' => 'integer'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
