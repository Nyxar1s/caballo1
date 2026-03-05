<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolePermissionController;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // User CRUD routes
    Route::apiResource('users', UserController::class);

    // Role and Permission Management Routes
    Route::prefix('roles-permissions')->group(function () {
        // Roles
        Route::post('/roles', [RolePermissionController::class, 'createRole']);
        Route::get('/roles', [RolePermissionController::class, 'getRoles']);
        Route::delete('/roles/{roleId}', [RolePermissionController::class, 'deleteRole']);

        // Permissions
        Route::post('/permissions', [RolePermissionController::class, 'createPermission']);
        Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);
        Route::delete('/permissions/{permissionId}', [RolePermissionController::class, 'deletePermission']);

        // Assign permission to role
        Route::post('/assign-permission-to-role', [RolePermissionController::class, 'assignPermissionToRole']);

        // User roles and permissions
        Route::post('/assign-role-to-user', [RolePermissionController::class, 'assignRoleToUser']);
        Route::post('/remove-role-from-user', [RolePermissionController::class, 'removeRoleFromUser']);
        Route::get('/user/{userId}/roles-permissions', [RolePermissionController::class, 'getUserRolesAndPermissions']);
    });
});
