<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPreferencesRequest;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Show settings page with normalized preferences.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('reconciliation.settings', [
            'preferences' => $this->normalizePreferences($user->preferences ?? []),
        ]);
    }

    /**
     * Persist validated user preferences.
     */
    public function update(UpdateUserPreferencesRequest $request)
    {
        $user = $request->user();
        $currentPreferences = $this->normalizePreferences($user->preferences ?? []);
        $prefs = $this->normalizePreferences(array_merge($currentPreferences, $request->validated()));

        $user->preferences = $prefs;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully.',
            'preferences' => $prefs,
        ]);
    }

    /**
     * Apply defaults and type-safe normalization.
     */
    private function normalizePreferences(array $raw): array
    {
        $defaults = [
            'theme' => 'dark',
            'grid_density' => 'normal',
            'compact_sidebar' => false,
            'email_notifications' => true,
            'auto_refresh' => true,
            'export_format' => 'xlsx',
            'page_size' => 50,
        ];

        $merged = array_merge($defaults, $raw);

        return [
            'theme' => in_array($merged['theme'], ['dark', 'light'], true) ? $merged['theme'] : 'dark',
            'grid_density' => in_array($merged['grid_density'], ['compact', 'normal', 'comfortable'], true) ? $merged['grid_density'] : 'normal',
            'compact_sidebar' => (bool) $merged['compact_sidebar'],
            'email_notifications' => (bool) $merged['email_notifications'],
            'auto_refresh' => (bool) $merged['auto_refresh'],
            'export_format' => in_array($merged['export_format'], ['xlsx', 'csv', 'pdf'], true) ? $merged['export_format'] : 'xlsx',
            'page_size' => in_array((int) $merged['page_size'], [25, 50, 100], true) ? (int) $merged['page_size'] : 50,
        ];
    }
}
