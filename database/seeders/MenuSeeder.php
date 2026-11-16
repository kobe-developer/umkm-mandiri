<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/** 30 produk dari foto daftar menu (lihat PRD "Data awal produk"). */
class MenuSeeder extends Seeder
{
    private const MENU = [
        'Makanan' => [
            ['Nasi + Ayam Penyet', 13000],
            ['Nasi + Telor', 10000],
            ['Nasi + Indomie', 10000],
            ['Indomie + Telor', 10000],
            ['Ayam Penyet', 10000],
            ['Tempe', 2000],
            ['Tahu', 2000],
            ['Usus', 2000],
            ['Ati/Ampela', 2000],
            ['Otak-otak', 2000],
            ['Nasi Putih', 5000],
            ['Extra Sambel', 3000],
        ],
        'Minuman' => [
            ['Es Teh Jumbo', 4000],
            ['Teh Hangat', 3000],
            ['Es Teajus', 3000],
            ['Es Goodday', 5000],
            ['Es Jasjus', 3000],
            ['Es Nutrisari', 5000],
            ['Es Milo', 5000],
            ['Mineral Botol', 3000],
            // Harga belum ada → disembunyikan dari menu online & nonaktif sampai owner mengisi harga
            ['Aneka Minum', 0, ['is_active' => false, 'show_online' => false]],
        ],
        'Snack' => [
            ['Keju Aroma Coklat', 10000],
            ['Keju Aroma Strawberry', 10000],
            ['Keju Aroma Matcha', 10000],
            ['Keju Aroma Tiramisu', 12000],
            ['Keju Aroma C. Crunch', 12000],
            ['Pisang Crispy Coklat', 10000],
            ['Pisang Crispy Strawberry', 10000],
            ['Pisang Crispy Matcha', 10000],
            ['Pisang Crispy Tiramisu', 12000],
        ],
    ];

    public function run(): void
    {
        $categoryOrder = 1;
        foreach (self::MENU as $categoryName => $products) {
            $category = Category::query()->firstOrCreate(['name' => $categoryName], ['sort_order' => $categoryOrder++]);

            foreach ($products as $index => $row) {
                [$name, $price] = $row;
                Product::query()->firstOrCreate(
                    ['name' => $name, 'category_id' => $category->id],
                    ['price' => $price, 'sort_order' => $index + 1, ...($row[2] ?? [])],
                );
            }
        }
    }
}
