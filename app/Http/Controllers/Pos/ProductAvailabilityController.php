<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Kasir: lihat produk & stok, toggle Habis/Tersedia (langsung berlaku di menu online). */
class ProductAvailabilityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Pos/Products', [
            'categories' => fn () => Category::ordered()->get(['id', 'name']),
            'products' => fn () => Product::active()->with('category:id,name')->orderBy('category_id')->orderBy('name')->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category_id' => $p->category_id,
                    'category' => $p->category?->name,
                    'price' => $p->price,
                    'is_available' => $p->is_available,
                    'is_sold_out' => $p->is_sold_out,
                    'show_online' => $p->show_online,
                    'track_stock' => $p->track_stock,
                    'stock' => $p->stock,
                    'is_low_stock' => $p->is_low_stock,
                ]),
        ]);
    }

    public function update(Request $request, Product $product, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate(['is_available' => ['required', 'boolean']]);
        $original = $product->getAttributes();
        $product->update($data);
        $logger->logChanges('availability', $product, $original, $product->name.($data['is_available'] ? ' tersedia' : ' habis'));

        return back()->with('success', "{$product->name} ditandai ".($data['is_available'] ? 'tersedia' : 'habis').'.');
    }
}
