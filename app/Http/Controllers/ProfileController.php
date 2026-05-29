<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function me()
    {
        $user = auth()->user();
        $profile = $user->profile;

        if (!$profile) {
            $profile = Profile::create(['user_id' => $user->id]);
        }

        return response()->json([
            'success' => true,
            'data' => $profile,
        ], 200);
    }

    /**
     * Get a specific user's profile.
     */
    public function show(User $user)
    {
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile,
        ], 200);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        try {
            $user = auth()->user();
            $profile = $user->profile;

            if (!$profile) {
                $profile = Profile::create(['user_id' => $user->id]);
            }

            $validated = $request->validate([
                'avatar_url' => 'sometimes|nullable|url',
                'bio' => 'sometimes|nullable|string|max:500',
                'phone' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:255',
                'city' => 'sometimes|nullable|string|max:100',
                'country' => 'sometimes|nullable|string|max:100',
                'birth_date' => 'sometimes|nullable|date',
                'website' => 'sometimes|nullable|url',
                'gender' => 'sometimes|nullable|in:male,female,other',
            ]);

            $profile->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $profile,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update another user's profile (admin only).
     */
    public function updateUser(Request $request, User $user)
    {
        try {
            $profile = $user->profile;

            if (!$profile) {
                $profile = Profile::create(['user_id' => $user->id]);
            }

            $validated = $request->validate([
                'avatar_url' => 'sometimes|nullable|url',
                'bio' => 'sometimes|nullable|string|max:500',
                'phone' => 'sometimes|nullable|string|max:20',
                'address' => 'sometimes|nullable|string|max:255',
                'city' => 'sometimes|nullable|string|max:100',
                'country' => 'sometimes|nullable|string|max:100',
                'birth_date' => 'sometimes|nullable|date',
                'website' => 'sometimes|nullable|url',
                'gender' => 'sometimes|nullable|in:male,female,other',
            ]);

            $profile->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $profile,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
