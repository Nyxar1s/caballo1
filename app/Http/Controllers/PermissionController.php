<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    /**
     * Get all permissions with pagination.
     */
    public function index(Request $request)
    {
        $query = Permission::query();

        // Filter by guard
        if ($request->has('guard') && $request->input('guard')) {
            $query->forGuard($request->input('guard'));
        }

        // Search by name
        if ($request->has('search') && $request->input('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $permissions = $query->orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * Create a new permission.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:permissions,name',
                'guard_name' => 'required|string|in:web,sanctum,api',
                'description' => 'sometimes|nullable|string|max:500',
            ]);

            $permission = Permission::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => $permission,
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
     * Get a specific permission with its roles.
     */
    public function show(Permission $permission)
    {
        $permission->load(['roles']);

        return response()->json([
            'success' => true,
            'data' => $permission,
        ], 200);
    }

    /**
     * Update a permission.
     */
    public function update(Request $request, Permission $permission)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|unique:permissions,name,' . $permission->id,
                'guard_name' => 'sometimes|required|string|in:web,sanctum,api',
                'description' => 'sometimes|nullable|string|max:500',
            ]);

            $permission->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission,
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
     * Delete a permission.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ], 200);
    }
}
