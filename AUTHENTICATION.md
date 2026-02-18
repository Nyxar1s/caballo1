# Laravel Sanctum Authentication Guide

This project now has Laravel Sanctum authentication with login, registration, and logout functionality implemented.

## Endpoints

### Public Routes (No Authentication Required)

#### Register
- **URL:** `POST /api/register`
- **Description:** Create a new user account
- **Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
- **Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2026-02-18T10:30:00.000000Z",
            "updated_at": "2026-02-18T10:30:00.000000Z"
        },
        "token": "1|abcdef123456..."
    }
}
```

#### Login
- **URL:** `POST /api/login`
- **Description:** Authenticate user and receive API token
- **Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```
- **Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2026-02-18T10:30:00.000000Z",
            "updated_at": "2026-02-18T10:30:00.000000Z"
        },
        "token": "1|abcdef123456..."
    }
}
```

### Protected Routes (Authentication Required)

To access protected routes, include the token in the request header:
```
Authorization: Bearer {token}
```

#### Get Current User
- **URL:** `GET /api/user`
- **Description:** Get the authenticated user's information
- **Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-02-18T10:30:00.000000Z",
        "updated_at": "2026-02-18T10:30:00.000000Z"
    }
}
```

#### Logout
- **URL:** `POST /api/logout`
- **Description:** Logout the authenticated user (revokes current token)
- **Response (200):**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

#### User CRUD Operations
All user CRUD routes are now protected and require authentication:
- `GET /api/users` - List all users
- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get specific user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user

## Testing with cURL

### Register a new user
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Get current user (replace TOKEN with actual token)
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer TOKEN"
```

### Logout (replace TOKEN with actual token)
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer TOKEN"
```

## Configuration

Sanctum configuration is in `config/sanctum.php`. Key settings:

- **Stateful Domains:** Configure domains that should use stateful authentication (cookies)
  - Default: `localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1`
  - Can be overridden via `SANCTUM_STATEFUL_DOMAINS` environment variable

- **Expiration:** Token expiration time in minutes (null = no expiration)

- **Guards:** Authentication guard used by Sanctum (default: 'web')

## Key Files Modified

1. **app/Http/Controllers/AuthController.php** - New controller with register, login, logout, and user methods
2. **app/Models/User.php** - Added `HasApiTokens` trait for Sanctum support
3. **routes/api.php** - Reorganized to separate public and protected routes
4. **config/auth.php** - Added 'sanctum' guard configuration

## Security Notes

- Passwords are hashed using Laravel's `Hash::make()` before storage
- API tokens are stored securely in the `personal_access_tokens` table
- Password confirmation is required during registration
- All protected routes require valid token in Authorization header
- Tokens can be revoked by deleting them from the database

## Token Management

Tokens are created using:
```php
$token = $user->createToken('auth_token')->plainTextToken;
```

Tokens can be revoked (logout) using:
```php
$request->user()->currentAccessToken()->delete();
```

To revoke all tokens for a user:
```php
$user->tokens()->delete();
```
