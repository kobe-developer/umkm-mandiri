<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Waktu masuk status Diproses & Siap, untuk riwayat status yang dilihat pelanggan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('processing_at')->nullable()->after('confirmed_at');
            $table->timestamp('ready_at')->nullable()->after('processing_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['processing_at', 'ready_at']);
        });
    }
};
