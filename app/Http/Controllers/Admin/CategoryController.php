<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Support\BusinessRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Categories', [
            'categories' => Category::ordered()->withCount('products')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::create($this->validated($request));
        $this->logger->log('create', $category, null, $category->getAttributes(), "Tambah kategori {$category->name}");

        return back()->with('success', "Kategori {$category->name} ditambahkan.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $original = $category->getAttributes();
        $category->update($this->validated($request, $category));
        $this->logger->logChanges('update', $category, $original, "Ubah kategori {$category->name}");

        return back()->with('success', "Kategori {$category->name} diperbarui.");
    }

    /** Kategori yang masih punya produk tidak bisa dihapus (hanya dinonaktifkan). */
    public function destroy(Category $category): RedirectResponse
    {
        BusinessRule::check(
            ! $category->products()->withTrashed()->exists(),
            'Kategori masih punya produk. Nonaktifkan saja.',
            'category',
        );

        $category->delete();
        $this->logger->log('delete', $category, ['name' => $category->name], null, "Hapus kategori {$category->name}");

        return back()->with('success', "Kategori {$category->name} dihapus.");
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('categories', 'name')->ignore($category?->id)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);
    }
}
