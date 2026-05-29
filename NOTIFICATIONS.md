# CRUD Notifications System

## Overview

This Laravel application now includes a comprehensive CRUD notification system that automatically notifies users when Create, Read, Update, and Delete operations are performed on users.

## Features

- **Automatic Notifications**: Users are automatically notified when CRUD operations occur
- **Database Storage**: Notifications are stored in the database for persistence
- **Email Notifications**: Optional email notifications for important events
- **Notification Management**: API endpoints to manage and view notifications
- **Read/Unread Tracking**: Track which notifications have been read

## How It Works

### Events
The system dispatches events for each CRUD operation:

1. **UserCreated** - Fired when a new user is created
2. **UserUpdated** - Fired when a user's information is updated
3. **UserDeleted** - Fired when a user is deleted

### Listeners
The `SendCrudNotification` listener handles these events and:

- Sends notifications to the affected user
- Sends notifications to the user who performed the action (if different)
- Stores notification data in the database

### Notifications
The `CrudNotification` class handles:

- Sending notifications via database channel
- Sending email notifications (when configured)
- Formatting notification data

## API Endpoints

### Get All Notifications
```
GET /api/notifications
Headers: Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "01abc123def456",
        "type": "App\\Notifications\\CrudNotification",
        "notifiable_id": 1,
        "data": {
          "action": "created",
          "model": "User",
          "message": "New User created",
          "details": {...}
        },
        "read_at": null,
        "created_at": "2026-05-29T10:30:00Z"
      }
    ],
    "total": 50,
    "per_page": 15
  }
}
```

### Get Unread Notifications Count
```
GET /api/notifications/unread-count
Headers: Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "unread_count": 5
}
```

### Mark Notification as Read
```
POST /api/notifications/mark-as-read
Headers: Authorization: Bearer {token}
Content-Type: application/json

{
  "notification_id": "01abc123def456"
}
```

Response:
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### Mark All Notifications as Read
```
POST /api/notifications/mark-all-as-read
Headers: Authorization: Bearer {token}
```

Response:
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

## User CRUD Operations

### Create User - Triggers UserCreated Event
```
POST /api/users
Headers: Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

The newly created user receives:
```
Subject: New User created
Message: A new User has been created.
Details: {"name": "John Doe", "email": "john@example.com"}
```

### Update User - Triggers UserUpdated Event
```
PATCH /api/users/{id}
Headers: Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com"
}
```

The updated user receives:
```
Subject: User updated
Message: A User has been updated.
Changes: {"name": "John Doe", "email": "john@example.com"}
```

### Delete User - Triggers UserDeleted Event
```
DELETE /api/users/{id}
Headers: Authorization: Bearer {token}
```

The admin/operator who deleted the user receives:
```
Subject: User deleted
Message: A User has been deleted.
Details: {"deleted_user": "John Doe", "deleted_email": "john@example.com", "user_id": 5}
```

## Configuration

### Environment Variables for Email Notifications

To enable email notifications, configure your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=notifications@example.com
MAIL_FROM_NAME="Your App"
```

### Queue Configuration

For better performance, configure a queue driver (database, Redis, etc.) in your `.env`:

```env
QUEUE_CONNECTION=database
```

Then run the queue worker:
```bash
php artisan queue:work
```

## Database Schema

The notifications table stores all notification data:

```sql
CREATE TABLE notifications (
    id ULID PRIMARY KEY,
    user_id UNSIGNED BIG INT FOREIGN KEY,
    type VARCHAR(255),
    data LONGTEXT,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX (user_id, created_at)
);
```

## Running Migrations

To create the notifications table:

```bash
php artisan migrate
```

## Testing

You can test the notification system using API calls:

1. **Register/Login** to get an authentication token
2. **Create a user** - You should receive notifications
3. **Query notifications** - Check the `/api/notifications` endpoint
4. **Update a user** - Verify the updated notification is created
5. **Delete a user** - Verify the deletion notification is created

## File Structure

- **Events**: `/app/Events/` - Contains `UserCreated`, `UserUpdated`, `UserDeleted` events
- **Listeners**: `/app/Listeners/` - Contains `SendCrudNotification` listener
- **Notifications**: `/app/Notifications/` - Contains `CrudNotification` class
- **Controllers**: `/app/Http/Controllers/UserController.php` - Handles CRUD and notification endpoints
- **Providers**: `/app/Providers/AppServiceProvider.php` - Registers event listeners
- **Migrations**: `/database/migrations/` - Contains notifications table migration

## Future Enhancements

- Add webhook notifications
- Add SMS notifications via Nexmo/Twilio
- Add push notifications for mobile apps
- Add user notification preferences
- Add notification templates
- Add bulk operations tracking
