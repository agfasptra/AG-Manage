<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories accessible by the user.
     */
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        // System default categories (user_id = null)
        $defaultCategories = Category::whereNull('user_id')
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // User's custom categories
        $userCategories = Category::where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('defaultCategories', 'userCategories'));
    }

    /**
     * Show the form for creating a new custom category.
     */
    public function create(): View
    {
        return view('categories.create');
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:30', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'type.required' => 'Jenis kategori wajib dipilih.',
            'type.in' => 'Jenis kategori harus Pemasukan atau Pengeluaran.',
            'color.regex' => 'Format warna tidak valid (gunakan format hex, contoh: #16A34A).',
        ]);

        // Set defaults
        $validated['icon'] = $validated['icon'] ?? ($validated['type'] === 'income' ? 'plus-circle' : 'tag');
        $validated['color'] = $validated['color'] ?? ($validated['type'] === 'income' ? '#16A34A' : '#DC2626');

        $request->user()->categories()->create($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:30', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'type.required' => 'Jenis kategori wajib dipilih.',
            'type.in' => 'Jenis kategori harus Pemasukan atau Pengeluaran.',
            'color.regex' => 'Format warna tidak valid.',
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Toggle active/inactive status of a category.
     */
    public function toggleActive(Category $category): RedirectResponse
    {
        Gate::authorize('toggleActive', $category);

        $category->update(['is_active' => ! $category->is_active]);

        $message = $category->is_active
            ? 'Kategori berhasil diaktifkan kembali.'
            : 'Kategori berhasil dinonaktifkan.';

        return redirect()->route('categories.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified category (only if no transactions reference it).
     */
    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
