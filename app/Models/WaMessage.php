<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Disiapkan untuk integrasi WhatsApp (Fase 2). */
class WaMessage extends Model
{
    protected $fillable = ['order_id', 'whatsapp_number', 'type', 'status', 'body', 'error', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
