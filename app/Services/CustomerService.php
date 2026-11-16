<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Ambil (atau buat) pelanggan berdasarkan nomor WA lalu kunci barisnya.
     * insertOrIgnore menghindari duplicate key saat dua order pertama masuk bersamaan.
     * Harus dipanggil di dalam DB::transaction.
     */
    public function lockByWhatsapp(string $whatsappNumber, string $name): Customer
    {
        DB::table('customers')->insertOrIgnore([
            'whatsapp_number' => $whatsappNumber,
            'name' => $name,
            'total_orders' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Customer::where('whatsapp_number', $whatsappNumber)->lockForUpdate()->firstOrFail();
    }

    /** Order dengan nomor WA yang sama otomatis terhubung ke pelanggan yang sama. */
    public function recordOrder(string $name, string $whatsappNumber): Customer
    {
        $customer = $this->lockByWhatsapp($whatsappNumber, $name);
        $customer->fill([
            'name' => $name,
            'total_orders' => $customer->total_orders + 1,
            'last_order_at' => now(),
        ])->save();

        return $customer;
    }
}
