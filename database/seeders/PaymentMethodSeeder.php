<?php

namespace Database\Seeders;

use App\Enums\OrderChannel;
use App\Enums\PaymentMethodType;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $all = OrderChannel::values();

        $methods = [
            ['name' => 'Tunai', 'type' => PaymentMethodType::Cash, 'channels' => $all, 'sort_order' => 1],
            ['name' => 'QRIS', 'type' => PaymentMethodType::Qris, 'channels' => $all, 'sort_order' => 2],
            ['name' => 'Transfer Bank', 'type' => PaymentMethodType::Transfer, 'channels' => [OrderChannel::Online->value], 'sort_order' => 3,
                'bank_name' => 'BCA', 'account_number' => '0000000000', 'account_holder' => 'UMKM Mandiri', 'is_active' => false],
        ];

        foreach ($methods as $method) {
            PaymentMethod::query()->firstOrCreate(['name' => $method['name']], $method);
        }
    }
}
