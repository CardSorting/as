<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Settings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = Settings::first() ?? new Settings();
        return view('store.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme_config' => 'nullable|array',
            'theme_config.colors' => 'nullable|array',
            'theme_config.fonts' => 'nullable|array',
            'payment_settings' => 'nullable|array',
            'payment_settings.methods' => 'nullable|array',
            'notification_settings' => 'nullable|array',
            'notification_settings.email' => 'nullable|array',
        ]);

        $settings = Settings::first() ?? new Settings();
        $settings->fill($validated);
        $settings->save();

        return back()->with('success', 'Settings updated successfully.');
    }
}
