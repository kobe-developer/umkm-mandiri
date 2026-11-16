<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::query()->firstOrCreate([], [
            ...StoreSetting::defaults(),
            'store_name' => 'UMKM Mandiri',
            'delivery_note' => 'Free delivery sekitaran kampus',
            'receipt_note' => 'Terima kasih sudah berbelanja di UMKM Mandiri!',
        ]);
    }
}
