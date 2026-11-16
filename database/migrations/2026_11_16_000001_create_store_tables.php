<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name', 100);
            $table->string('address')->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('delivery_note')->nullable();
            $table->string('receipt_note')->nullable();
            $table->boolean('is_open')->default(false);
            $table->boolean('auto_schedule')->default(true);
            $table->json('operating_hours');
            $table->boolean('last_schedule_state')->nullable();
            $table->boolean('online_order_enabled')->default(true);
            $table->unsignedInteger('min_order')->default(0);
            $table->boolean('allow_pickup')->default(true);
            $table->boolean('allow_delivery')->default(true);
            $table->boolean('cashier_can_discount')->default(false);
            $table->boolean('cashier_can_view_revenue')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedInteger('price');
            $table->string('photo_path')->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_online')->default(true);
            $table->boolean('track_stock')->default(false);
            $table->integer('stock')->default(0);
            $table->unsignedInteger('stock_minimum')->default(0);
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'show_online']);
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('type', 20);
            $table->string('bank_name', 50)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('account_holder', 100)->nullable();
            $table->string('qris_image_path')->nullable();
            $table->json('channels');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('whatsapp_number', 20)->unique();
            $table->unsignedInteger('total_orders')->default(0);
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('opening_cash');
            $table->unsignedInteger('closing_cash_actual')->nullable();
            $table->unsignedInteger('closing_cash_system')->nullable();
            $table->integer('cash_difference')->nullable();
            $table->string('closing_note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('store_settings');
    }
};
