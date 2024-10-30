<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->paginate(12);
        return view('store.products.index', compact('products'));
    }

    public function create()
    {
        return view('store.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'files.*' => 'required_if:is_digital,true|file|max:102400',
            'is_digital' => 'boolean',
        ]);

        $files = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                // Store in isolated store directory
                $path = $file->store('products/' . Str::random(40), [
                    'disk' => 'store_files'
                ]);
                $files[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $product = Product::create([
            ...$validated,
            'files' => $files,
        ]);

        return redirect()->route('store.products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return view('store.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('store.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'files.*' => 'nullable|file|max:102400',
            'is_digital' => 'boolean',
        ]);

        $files = $product->files ?? [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('products/' . Str::random(40), [
                    'disk' => 'store_files'
                ]);
                $files[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                ];
            }
        }

        $product->update([
            ...$validated,
            'files' => $files,
        ]);

        return redirect()->route('store.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        // Delete associated files
        if (!empty($product->files)) {
            foreach ($product->files as $file) {
                Storage::disk('store_files')->delete($file['path']);
            }
        }

        $product->delete();

        return redirect()->route('store.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
