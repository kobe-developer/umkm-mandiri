<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Services\ImageService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ImageService $images,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:50'],
        ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => Product::with('category:id,name')
                ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
                ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->orderBy('category_id')->orderBy('sort_order')->orderBy('name')
                ->get(),
            'categories' => Category::ordered()->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categories' => Category::ordered()->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(ProductRequest $request, StockService $stock): RedirectResponse
    {
        $data = $request->validated();

        $product = DB::transaction(function () use ($request, $data, $stock) {
            $product = Product::create([
                ...$this->attributes($data),
                'photo_path' => $request->hasFile('photo') ? $this->images->storeCompressed($request->file('photo'), 'products') : null,
            ]);

            $initial = (int) ($data['initial_stock'] ?? 0);
            if ($product->track_stock && $initial > 0) {
                $stock->adjust($product, StockMovementType::In, $initial, 'Stok awal', $request->user());
            }

            return $product;
        });

        $this->logger->log('create', $product, null, $product->fresh()->getAttributes(), "Tambah produk {$product->name}");

        return redirect()->route('admin.products.index')->with('success', "Produk {$product->name} ditambahkan.");
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $product,
            'categories' => Category::ordered()->get(['id', 'name', 'is_active']),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $original = $product->getAttributes();
        $attributes = $this->attributes($data);

        if ($request->hasFile('photo')) {
            $this->images->delete($product->photo_path);
            $attributes['photo_path'] = $this->images->storeCompressed($request->file('photo'), 'products');
        } elseif (! empty($data['remove_photo'])) {
            $this->images->delete($product->photo_path);
            $attributes['photo_path'] = null;
        }

        // Harga lama di transaksi tidak berubah: harga disalin ke order_items
        $product->update($attributes);
        $this->logger->logChanges('update', $product, $original, "Ubah produk {$product->name}");

        return redirect()->route('admin.products.index')->with('success', "Produk {$product->name} diperbarui.");
    }

    /** Soft delete: riwayat transaksi tetap utuh. */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        $this->logger->log('delete', $product, ['name' => $product->name, 'price' => $product->price], null, "Hapus produk {$product->name}");

        return redirect()->route('admin.products.index')->with('success', "Produk {$product->name} dihapus.");
    }

    private function attributes(array $data): array
    {
        return [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'show_online' => $data['show_online'] ?? true,
            'track_stock' => $data['track_stock'] ?? false,
            'stock_minimum' => $data['stock_minimum'] ?? 0,
            'is_available' => $data['is_available'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
