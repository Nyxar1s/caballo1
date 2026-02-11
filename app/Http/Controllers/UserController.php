<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => User::all(),
        ], 200);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $validated['password'] = bcrypt($validated['password']);

            $user = User::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'data' => $user,
        ], 200);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|required|string|min:8',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user,
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
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ], 200);
    }
}
