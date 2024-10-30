<?php

namespace App\Http\Controllers;

use App\Models\StoreSilo;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stores = StoreSilo::where('user_id', auth()->id())->get();
        
        return view('dashboard', compact('stores'));
    }
}
