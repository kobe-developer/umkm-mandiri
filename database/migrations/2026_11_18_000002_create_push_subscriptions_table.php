<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use NotificationChannels\WebPush\PushSubscription;

/**
 * Langganan web push. Pemiliknya bisa User (owner/kasir) atau Order (pelanggan tanpa login).
 * Berbeda dari stub paket: endpoint TIDAK unik global, karena satu perangkat pelanggan bisa
 * mengikuti beberapa pesanan sekaligus. Unik per (pemilik, endpoint).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
            $table->string('endpoint', PushSubscription::ENDPOINT_MAX_LENGTH)->charset('ascii');
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();

            $table->unique(['subscribable_type', 'subscribable_id', 'endpoint'], 'push_subscriptions_owner_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
