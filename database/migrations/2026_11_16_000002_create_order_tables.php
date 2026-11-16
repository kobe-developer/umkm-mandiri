<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->date('sequence_date')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->string('payment_status', 20);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 50)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('table_number', 10)->nullable();
            $table->string('fulfillment', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('notes', 255)->nullable();
            $table->unsignedInteger('subtotal');
            $table->string('discount_type', 10)->nullable();
            $table->unsignedInteger('discount_value')->default(0);
            $table->unsignedInteger('discount');
            $table->unsignedInteger('total');
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('planned_payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->unsignedInteger('amount_received')->nullable();
            $table->unsignedInteger('change_amount')->nullable();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_token', 64)->nullable();
            $table->boolean('stock_deducted')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('void_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['status', 'payment_status']);
            $table->index(['channel', 'created_at']);
            $table->index(['whatsapp_number', 'status']);
            $table->index('completed_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 100);
            $table->string('category_name', 50)->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->string('notes', 100)->nullable();
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->integer('quantity');
            $table->integer('stock_after');
            $table->string('reason', 255)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 255)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });

        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('whatsapp_number', 20);
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->text('body')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_sequences');
    }
};
