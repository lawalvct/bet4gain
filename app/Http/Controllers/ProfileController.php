<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Upload or update the user's avatar.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar if it exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->storeAs(
            'avatars',
            Str::uuid() . '.' . $request->file('avatar')->extension(),
            'public'
        );

        $user->update(['avatar' => $path]);

        return response()->json([
            'message' => 'Avatar updated successfully.',
            'avatar' => Storage::url($path),
        ]);
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        return response()->json([
            'message' => 'Avatar removed.',
            'avatar' => null,
        ]);
    }

    /**
     * Update user settings/preferences (theme, sound, etc.)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.theme' => ['sometimes', 'string', 'in:dark,light,system'],
            'settings.sound_enabled' => ['sometimes', 'boolean'],
            'settings.sound_volume' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'settings.notifications_enabled' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $currentSettings = $user->settings ?? [];
        $newSettings = array_merge($currentSettings, $request->input('settings'));

        $user->update(['settings' => $newSettings]);

        return response()->json([
            'message' => 'Settings updated.',
            'settings' => $newSettings,
        ]);
    }
}
