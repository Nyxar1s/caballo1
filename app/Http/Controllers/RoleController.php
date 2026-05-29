<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Get all roles with pagination.
     */
    public function index(Request $request)
    {
        $query = Role::query();

        // Filter by guard
        if ($request->has('guard') && $request->input('guard')) {
            $query->forGuard($request->input('guard'));
        }

        // Search by name
        if ($request->has('search') && $request->input('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $roles = $query->with('permissions')->orderBy('name')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ], 200);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name',
                'guard_name' => 'required|string|in:web,sanctum,api',
                'description' => 'sometimes|nullable|string|max:500',
            ]);

            $role = Role::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role,
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
     * Get a specific role with its permissions and users.
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users' => function ($q) {
            $q->select('users.id', 'users.name', 'users.email');
        }]);

        return response()->json([
            'success' => true,
            'data' => $role,
        ], 200);
    }

    /**
     * Update a role.
     */
    public function update(Request $request, Role $role)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|unique:roles,name,' . $role->id,
                'guard_name' => 'sometimes|required|string|in:web,sanctum,api',
                'description' => 'sometimes|nullable|string|max:500',
            ]);

            $role->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role,
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
     * Delete a role.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ], 200);
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(Request $request, Role $role)
    {
        try {
            $validated = $request->validate([
                'permission_id' => 'required|exists:permissions,id',
            ]);

            $permission = Permission::findOrFail($validated['permission_id']);
            $role->givePermissionTo($permission);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to role successfully',
                'data' => $role->load('permissions'),
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
     * Remove a permission from a role.
     */
    public function removePermission(Request $request, Role $role)
    {
        try {
            $validated = $request->validate([
                'permission_id' => 'required|exists:permissions,id',
            ]);

            $permission = Permission::findOrFail($validated['permission_id']);
            $role->revokePermissionTo($permission);

            return response()->json([
                'success' => true,
                'message' => 'Permission removed from role successfully',
                'data' => $role->load('permissions'),
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
