<?php

namespace App\Http\Controllers;

use App\Models\StoreSilo;
use App\Services\StoreIsolation\StoreDestroyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreDeletionController extends Controller
{
    private StoreDestroyer $storeDestroyer;

    public function __construct(StoreDestroyer $storeDestroyer)
    {
        $this->storeDestroyer = $storeDestroyer;
        $this->middleware(['auth', 'verified']);
    }

    public function confirm(StoreSilo $store)
    {
        if (!Gate::allows('delete-store', $store)) {
            abort(403);
        }

        return view('stores.delete-confirmation', compact('store'));
    }

    public function destroy(Request $request, StoreSilo $store)
    {
        if (!Gate::allows('delete-store', $store)) {
            abort(403);
        }

        $request->validate([
            'confirmation' => ['required', 'string', 'in:' . $store->store_domain],
        ]);

        try {
            $this->storeDestroyer->destroy($store);

            return redirect()->route('dashboard')
                ->with('success', 'Your store has been permanently deleted.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete store. Please contact support.');
        }
    }
}
