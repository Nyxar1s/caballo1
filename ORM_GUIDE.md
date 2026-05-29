# Eloquent ORM Documentation

## Overview

This Laravel application includes a comprehensive Eloquent ORM setup with models, relationships, controllers, and API endpoints for managing Users, Profiles, Roles, Permissions, and Audit Logs.

## Database Schema

### Relationships Diagram

```
Users (1) ─── (1) Profiles
  │                ├── avatar_url
  │                ├── bio
  │                ├── phone
  │                ├── address
  │                ├── city
  │                ├── country
  │                ├── birth_date
  │                ├── website
  │                └── gender
  │
  ├─── (M) Roles [via model_has_roles]
  │
  ├─── (M) Permissions [via model_has_permissions]
  │
  └─── (M) AuditLogs
       ├── model_type
       ├── model_id
       ├── action (created, updated, deleted, viewed, exported)
       ├── old_values (JSON)
       ├── new_values (JSON)
       └── description

Roles (M) ─── (M) Permissions [via role_has_permissions]
  │
  └─── (M) Users [via model_has_roles]
```

## Models

### User Model
**File:** `app/Models/User.php`

**Traits:**
- `HasFactory` - Database seeding
- `Notifiable` - Handle notifications
- `HasApiTokens` - Sanctum API authentication
- `HasRoles` - Spatie Permission roles

**Relationships:**
```php
$user->profile()            // HasOne Profile
$user->auditLogs()          // HasMany AuditLog (logs created by user)
$user->subjectAuditLogs()   // HasMany AuditLog (logs about the user)
$user->roles()              // BelongsToMany Role (from Spatie)
$user->permissions()        // BelongsToMany Permission (from Spatie)
$user->notifications()      // HasMany Notification
```

**Usage Examples:**
```php
$user = auth()->user();

// Load user with relationships
$user->load(['profile', 'roles', 'permissions']);

// Access profile
$user->profile->bio;

// Check audit history
$user->auditLogs;
$user->subjectAuditLogs;  // Changes made to this user

// Check roles/permissions
$user->hasRole('admin');
$user->hasPermission('create.users');
```

### Profile Model
**File:** `app/Models/Profile.php`

**Columns:**
- `id` - Primary key
- `user_id` - Foreign key to users table
- `avatar_url` - URL to user's avatar image
- `bio` - User biography
- `phone` - Contact phone number
- `address` - Street address
- `city` - City
- `country` - Country
- `birth_date` - Date of birth
- `website` - Personal website URL
- `gender` - male | female | other

**Relationships:**
```php
$profile->user()  // BelongsTo User
```

**Usage Examples:**
```php
// Get user's profile
$profile = auth()->user()->profile;

// Create profile if not exists
$profile = auth()->user()->profile ?? Profile::create(['user_id' => auth()->id()]);

// Update profile
$profile->update([
    'bio' => 'Hello, I am a developer',
    'phone' => '+1234567890',
    'city' => 'New York',
]);
```

### Role Model
**File:** `app/Models/Role.php`

**Extends:** `Spatie\Permission\Models\Role`

**Columns:**
- `id` - Primary key
- `name` - Role name (e.g., 'admin', 'user')
- `guard_name` - Guard (web, sanctum, api)
- `description` - Role description
- `created_at` - Timestamp
- `updated_at` - Timestamp

**Relationships:**
```php
$role->permissions()  // BelongsToMany Permission
$role->users()        // BelongsToMany User
```

**Scopes:**
```php
$role->forGuard('sanctum')  // Filter by guard
```

**Usage Examples:**
```php
// Get all roles with permissions
$roles = Role::with('permissions')->get();

// Create a new role
$role = Role::create([
    'name' => 'editor',
    'guard_name' => 'sanctum',
    'description' => 'Can edit content',
]);

// Give permission to role
$role->givePermissionTo('edit.posts');

// Get role with users
$role->load('users');
$users = $role->users;
```

### Permission Model
**File:** `app/Models/Permission.php`

**Extends:** `Spatie\Permission\Models\Permission`

**Columns:**
- `id` - Primary key
- `name` - Permission name (e.g., 'create.users')
- `guard_name` - Guard (web, sanctum, api)
- `description` - Permission description
- `created_at` - Timestamp
- `updated_at` - Timestamp

**Relationships:**
```php
$permission->roles()  // BelongsToMany Role
```

**Scopes:**
```php
$permission->forGuard('sanctum')  // Filter by guard
```

**Usage Examples:**
```php
// Get all permissions
$permissions = Permission::all();

// Create permission
$permission = Permission::create([
    'name' => 'delete.users',
    'guard_name' => 'sanctum',
    'description' => 'Can delete users',
]);

// Get permission with roles
$permission->load('roles');
```

### AuditLog Model
**File:** `app/Models/AuditLog.php`

**Columns:**
- `id` - Primary key
- `user_id` - Who performed the action
- `model_type` - Model class name (e.g., 'App\Models\User')
- `model_id` - ID of the model that was changed
- `action` - created | updated | deleted | viewed | exported
- `old_values` - JSON of previous values
- `new_values` - JSON of new values
- `description` - Custom description
- `ip_address` - User's IP address
- `user_agent` - Browser user agent

**Relationships:**
```php
$auditLog->user()  // BelongsTo User (who performed action)
```

**Scopes:**
```php
$auditLog->forModel('App\Models\User')           // All audits for a model type
$auditLog->forModelId('App\Models\User', 5)      // Audits for specific model
$auditLog->forAction('created')                  // Audits for specific action
$auditLog->recentDays(30)                        // Last N days
```

**Usage Examples:**
```php
// Get all audit logs
$logs = AuditLog::with('user')->paginate();

// Get audit logs for a user model
$logs = AuditLog::forModelId(\App\Models\User::class, 5)->get();

// Get all user deletions
$logs = AuditLog::forModel(\App\Models\User::class)
    ->forAction('deleted')
    ->recentDays(7)
    ->get();

// Get logs by a specific user
$logs = auth()->user()->auditLogs;
```

## API Endpoints

### User Endpoints
```
GET    /api/users              - List all users (paginated)
POST   /api/users              - Create new user
GET    /api/users/{id}         - Get specific user
PATCH  /api/users/{id}         - Update user
DELETE /api/users/{id}         - Delete user
```

### Profile Endpoints
```
GET    /api/profile            - Get authenticated user's profile
PATCH  /api/profile            - Update authenticated user's profile
GET    /api/users/{id}/profile - Get specific user's profile
PATCH  /api/users/{id}/profile - Update specific user's profile (admin)
```

**Profile Request Body:**
```json
{
  "avatar_url": "https://example.com/avatar.jpg",
  "bio": "I am a developer",
  "phone": "+1234567890",
  "address": "123 Main St",
  "city": "New York",
  "country": "USA",
  "birth_date": "1990-01-15",
  "website": "https://example.com",
  "gender": "male"
}
```

### Role Endpoints
```
GET    /api/roles              - List all roles with permissions
POST   /api/roles              - Create new role
GET    /api/roles/{id}         - Get specific role with permissions & users
PATCH  /api/roles/{id}         - Update role
DELETE /api/roles/{id}         - Delete role
POST   /api/roles/{id}/permissions        - Assign permission to role
DELETE /api/roles/{id}/permissions        - Remove permission from role
```

**Role Request Body:**
```json
{
  "name": "editor",
  "guard_name": "sanctum",
  "description": "Can edit content"
}
```

**Assign Permission Request:**
```json
{
  "permission_id": 5
}
```

### Permission Endpoints
```
GET    /api/permissions        - List all permissions
POST   /api/permissions        - Create new permission
GET    /api/permissions/{id}   - Get specific permission with roles
PATCH  /api/permissions/{id}   - Update permission
DELETE /api/permissions/{id}   - Delete permission
```

**Permission Request Body:**
```json
{
  "name": "edit.posts",
  "guard_name": "sanctum",
  "description": "Can edit posts"
}
```

### Audit Log Endpoints
```
GET    /api/audit-logs                              - List audit logs (paginated)
GET    /api/audit-logs/statistics                   - Get audit statistics
GET    /api/audit-logs/{id}                         - Get specific audit log
GET    /api/audit-logs/model/{modelType}/{modelId}  - Get audit logs for specific model
GET    /api/users/{userId}/audit-logs               - Get audit logs by user
```

**Audit Logs Query Parameters:**
```
?model_type=App\Models\User  - Filter by model type
?action=created               - Filter by action
?user_id=1                    - Filter by user who performed action
?from_date=2026-05-01         - From date
?to_date=2026-05-31           - To date
?page=1                       - Pagination
```

**Statistics Response:**
```json
{
  "success": true,
  "data": {
    "total_logs": 245,
    "by_action": [
      {"action": "created", "count": 50},
      {"action": "updated", "count": 180},
      {"action": "deleted", "count": 15}
    ],
    "by_model_type": [
      {"model_type": "App\\Models\\User", "count": 200},
      {"model_type": "App\\Models\\Post", "count": 45}
    ],
    "by_user": [...]
  }
}
```

## Querying with Relationships

### Eager Loading
```php
// Load relationships with main query
$users = User::with(['profile', 'roles', 'permissions'])->get();

// Nested relationships
$roles = Role::with('permissions.roles')->get();
```

### Lazy Loading
```php
// Load after fetching
$user = User::find(1);
$user->load(['profile', 'auditLogs']);
```

### Filtering with Relationships
```php
// Users with specific role
$users = User::whereHas('roles', function ($query) {
    $query->where('name', 'admin');
})->get();

// Permissions assigned to role
$permissions = Permission::whereHas('roles', function ($query) {
    $query->where('id', 5);
})->get();
```

## Migrations

Run all migrations to set up the ORM:

```bash
php artisan migrate
```

**New tables created:**
- `profiles` - User profile information
- `audit_logs` - Track model changes
- **Modified tables:**
  - `permissions` - Added `description` column
  - `roles` - Added `description` column

## Key Features

✅ **Complete ORM Setup** - All models have proper relationships
✅ **Audit Logging** - Track all model changes
✅ **Role-Based Access** - Extended Spatie Permission models
✅ **User Profiles** - Extended user information
✅ **API Endpoints** - RESTful endpoints for all models
✅ **Query Scopes** - Helper scopes for common queries
✅ **Pagination** - Built-in pagination on all list endpoints
✅ **Filtering** - Query parameters for filtering results

## Best Practices

1. **Always use relationships** - Don't manually join tables
2. **Eager load** - Prevent N+1 queries with `with()`
3. **Use scopes** - Leverage query scopes for common filters
4. **Validate input** - All endpoints validate request data
5. **Check permissions** - Use Spatie traits for role/permission checking
6. **Audit important actions** - Log sensitive model changes

## Example Usage

```php
// Create a user with profile
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
]);

Profile::create([
    'user_id' => $user->id,
    'bio' => 'Hello',
    'city' => 'New York',
]);

// Assign roles and permissions
$role = Role::find(1);
$user->assignRole($role);

// Track in audit log would be automatic if integrated

// Query with relationships
$user->load('profile', 'roles', 'permissions', 'auditLogs');

// Get audit history
$audits = AuditLog::forModelId(User::class, $user->id)->get();
```

## Integration with CRUD Notifications

The CRUD notification system (from previous implementation) works alongside the ORM:

- When users are created/updated/deleted, events are dispatched
- Notifications are stored in the `notifications` table
- AuditLog can be extended to log these events automatically
- Combine both for complete audit trail + notifications

## Next Steps

1. Run migrations: `php artisan migrate`
2. Create initial roles and permissions via API
3. Assign roles to users
4. Monitor changes via audit logs
5. Extend models with custom methods as needed
