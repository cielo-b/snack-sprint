<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = [
            'payment_gateway' => SystemSetting::getValue('payment_gateway', 'mtn'),
            'payment_expiry' => SystemSetting::getValue('payment_expiry', 15),
        ];

        return view('settings', compact('settings'));
    }

    /**
     * Update the system settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'payment_gateway' => 'required|string|in:mopay,irembopay',
            'payment_expiry' => 'required|integer|min:1|max:60',
        ]);

        SystemSetting::setValue('payment_gateway', $validated['payment_gateway']);
        SystemSetting::setValue('payment_expiry', $validated['payment_expiry']);

        return redirect()->route('settings.index')->with('success', 'Settings updated successfully');
    }
}
