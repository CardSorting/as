<?php

namespace App\Http\Controllers;

use App\Services\StoreIsolation\StoreCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCreationController extends Controller
{
    private StoreCreator $storeCreator;

    public function __construct(StoreCreator $storeCreator)
    {
        $this->storeCreator = $storeCreator;
        $this->middleware(['auth', 'verified']);
    }

    public function create()
    {
        return view('stores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('store_silos', 'store_domain'),
            ],
        ]);

        $store = $this->storeCreator->create([
            'user_id' => auth()->id(),
            'domain' => $validated['domain'],
        ]);

        return redirect()->route('store.dashboard', ['subdomain' => $store->store_domain])
            ->with('success', 'Your store has been created successfully!');
    }
}
