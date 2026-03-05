<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RolePermissionController extends Controller
{
    /**
     * Create a new role
     */
    public function createRole(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name',
            ]);

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

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
     * Create a new permission
     */
    public function createPermission(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:permissions,name',
            ]);

            $permission = Permission::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
            ]);

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
     * Assign permission to role
     */
    public function assignPermissionToRole(Request $request)
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|exists:roles,id',
                'permission_id' => 'required|exists:permissions,id',
            ]);

            $role = Role::findOrFail($validated['role_id']);
            $permission = Permission::findOrFail($validated['permission_id']);

            $role->givePermissionTo($permission);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to role successfully',
                'data' => [
                    'role' => $role,
                    'permission' => $permission,
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign role to user
     */
    public function assignRoleToUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:roles,id',
            ]);

            $user = User::findOrFail($validated['user_id']);
            $role = Role::findOrFail($validated['role_id']);

            $user->assignRole($role);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned to user successfully',
                'data' => [
                    'user' => $user,
                    'roles' => $user->getRoleNames(),
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get all roles
     */
    public function getRoles()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ], 200);
    }

    /**
     * Get all permissions
     */
    public function getPermissions()
    {
        $permissions = Permission::all();

        return response()->json([
            'success' => true,
            'data' => $permissions,
        ], 200);
    }

    /**
     * Get user roles and permissions
     */
    public function getUserRolesAndPermissions($userId)
    {
        try {
            $user = User::findOrFail($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getPermissionNames(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    /**
     * Remove role from user
     */
    public function removeRoleFromUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role_id' => 'required|exists:roles,id',
            ]);

            $user = User::findOrFail($validated['user_id']);
            $role = Role::findOrFail($validated['role_id']);

            $user->removeRole($role);

            return response()->json([
                'success' => true,
                'message' => 'Role removed from user successfully',
                'data' => [
                    'user' => $user,
                    'roles' => $user->getRoleNames(),
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete role
     */
    public function deleteRole($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }
    }

    /**
     * Delete permission
     */
    public function deletePermission($permissionId)
    {
        try {
            $permission = Permission::findOrFail($permissionId);
            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }
    }
}
