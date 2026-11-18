<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Harga promo per produk untuk periode tanggal tertentu (WIB) dan kanal tertentu. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->json('channels');
            $table->date('active_from');
            $table->date('active_until');
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'active_from', 'active_until']);
            $table->index(['active_from', 'active_until']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('original_price')->nullable()->after('unit_price');
            $table->foreignId('promotion_id')->nullable()->after('original_price')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn('original_price');
        });

        Schema::dropIfExists('promotions');
    }
};
