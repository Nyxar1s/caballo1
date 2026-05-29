<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // User CRUD routes
    Route::apiResource('users', UserController::class);

    // Notification routes
    Route::get('/notifications', [UserController::class, 'getNotifications']);
    Route::get('/notifications/unread-count', [UserController::class, 'getUnreadNotificationsCount']);
    Route::post('/notifications/mark-as-read', [UserController::class, 'markNotificationAsRead']);
    Route::post('/notifications/mark-all-as-read', [UserController::class, 'markAllNotificationsAsRead']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'me']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::get('/users/{user}/profile', [ProfileController::class, 'show']);
    Route::patch('/users/{user}/profile', [ProfileController::class, 'updateUser']);

    // Audit Log routes
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/statistics', [AuditLogController::class, 'statistics']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('/audit-logs/model/{modelType}/{modelId}', [AuditLogController::class, 'forModel']);
    Route::get('/users/{user}/audit-logs', [AuditLogController::class, 'userLogs']);

    // Role management routes (ORM based)
    Route::apiResource('roles', RoleController::class);
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermission']);
    Route::delete('/roles/{role}/permissions', [RoleController::class, 'removePermission']);

    // Permission management routes (ORM based)
    Route::apiResource('permissions', PermissionController::class);

    // Legacy Role and Permission Management Routes
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
