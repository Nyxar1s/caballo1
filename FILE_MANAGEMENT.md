# File Management System Documentation

## Overview

This Laravel application includes a comprehensive file management system with support for file uploads, storage, deletion, and access control. The system automatically tracks file metadata, supports multiple storage disks, and provides REST API endpoints for all file operations.

## Features

✅ **File Upload** - Secure file uploads with validation
✅ **File Storage** - Support for multiple storage disks (local, S3, etc.)
✅ **File Deletion** - Soft delete with restore capability
✅ **Access Control** - Public/private file visibility
✅ **File Metadata** - Automatic tracking of file information
✅ **File Organization** - Organized by user and type
✅ **Download Streaming** - Secure file downloads
✅ **File Statistics** - Storage usage tracking
✅ **File Filtering** - Filter by type, date, ownership
✅ **File Restore** - Recover deleted files

## Database Schema

### Files Table

```sql
CREATE TABLE files (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL FOREIGN KEY,
    original_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path TEXT NOT NULL,
    mime_type VARCHAR(100),
    size BIGINT UNSIGNED,
    extension VARCHAR(10),
    disk VARCHAR(100) DEFAULT 'public',
    description TEXT,
    is_public BOOLEAN DEFAULT TRUE,
    uploaded_by BIGINT FOREIGN KEY,
    metadata JSON,
    deleted_at TIMESTAMP NULL (soft deletes),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX (user_id, created_at),
    INDEX (uploaded_by, created_at),
    INDEX (mime_type),
    INDEX (is_public),
    INDEX (deleted_at)
);
```

## Models

### File Model
**File:** `app/Models/File.php`

**Attributes:**
- `id` - Primary key
- `user_id` - File owner
- `original_name` - Original filename
- `file_name` - Stored filename (hashed)
- `file_path` - Path in storage
- `mime_type` - File MIME type
- `size` - File size in bytes
- `extension` - File extension
- `disk` - Storage disk name
- `description` - File description
- `is_public` - Public/private visibility
- `uploaded_by` - User who uploaded the file
- `metadata` - JSON metadata

**Relationships:**
```php
$file->user()           // BelongsTo User (owner)
$file->uploadedByUser() // BelongsTo User (uploader)
```

**Accessors:**
```php
$file->file_url              // Get file URL
$file->human_readable_size   // Get human-readable size (e.g., "2.5 MB")
```

**Scopes:**
```php
File::forUser($userId)                    // Files for a user
File::public()                            // Public files only
File::private()                           // Private files only
File::byMimeType('image')                 // Filter by MIME type prefix
File::images()                            // Image files
File::documents()                         // Document files (PDF, Word, Excel)
File::videos()                            // Video files
File::audio()                             // Audio files

// Static methods
File::getTotalSize($userId)               // Total storage used by user
```

**Soft Deletes:**
```php
// Soft delete
$file->delete();

// Restore soft-deleted file
$file->restore();

// Force delete
$file->forceDelete();

// Query with soft deleted
File::withTrashed()->get();
File::onlyTrashed()->get();
```

## Services

### FileService
**File:** `app/Services/FileService.php`

Handles all file operations including upload, delete, restore, and metadata management.

**Key Methods:**

```php
/**
 * Upload a file
 * @return File|null
 */
$fileService->upload(
    UploadedFile $file,
    int $userId,
    ?string $description = null,
    bool $isPublic = true,
    ?string $disk = 'public'
): File

/**
 * Delete file (soft delete)
 */
$fileService->delete(File $file): bool

/**
 * Permanently delete file
 */
$fileService->forceDelete(File $file): bool

/**
 * Restore soft-deleted file
 */
$fileService->restore(File $file): bool

/**
 * Update file metadata
 */
$fileService->update(File $file, array $data): File

/**
 * Move file to another disk
 */
$fileService->moveFile(File $file, string $newDisk): File

/**
 * Get file download stream
 */
$fileService->getDownloadStream(File $file)

/**
 * Configuration methods
 */
$fileService->setMaxFileSize(int $bytes): self
$fileService->setAllowedMimeTypes(array $types): self
$fileService->addAllowedMimeType(string $type): self
```

**Default Allowed MIME Types:**
- Images: image/jpeg, image/png, image/gif, image/webp
- Documents: application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
- Media: video/mp4, video/mpeg, audio/mpeg, audio/wav
- Text: text/plain, text/csv

**Default Max File Size:** 100 MB

## API Endpoints

### Upload File
```
POST /api/files
Headers: Authorization: Bearer {token}, Content-Type: multipart/form-data

Form Data:
{
  "file": <binary file>,
  "description": "Optional file description",
  "is_public": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "File uploaded successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "original_name": "document.pdf",
    "file_name": "document_abc123def456.pdf",
    "file_path": "files/1/document_abc123def456.pdf",
    "mime_type": "application/pdf",
    "size": 2048576,
    "extension": "pdf",
    "disk": "public",
    "description": "Optional file description",
    "is_public": true,
    "file_url": "http://example.com/storage/files/1/document_abc123def456.pdf",
    "human_readable_size": "2.05 MB",
    "created_at": "2026-05-29T10:30:00Z",
    "updated_at": "2026-05-29T10:30:00Z"
  }
}
```

### List Files
```
GET /api/files
Headers: Authorization: Bearer {token}

Query Parameters:
  ?type=images           - Filter by type (images, documents, videos, audio, public, private)
  ?mime_type=image/jpeg  - Filter by MIME type
  ?search=filename       - Search by filename or description
  ?sort_by=created_at    - Sort by field
  ?sort_order=desc       - Sort order (asc/desc)
  ?page=1                - Pagination
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "original_name": "document.pdf",
        "file_url": "...",
        "size": 2048576,
        "human_readable_size": "2.05 MB",
        "mime_type": "application/pdf",
        "is_public": true,
        "created_at": "2026-05-29T10:30:00Z"
        ...
      }
    ],
    "total": 50,
    "per_page": 20
  }
}
```

### Get File Details
```
GET /api/files/{id}
Headers: Authorization: Bearer {token}
```

### Update File
```
PATCH /api/files/{id}
Headers: Authorization: Bearer {token}
Content-Type: application/json

{
  "description": "Updated description",
  "is_public": false
}
```

### Delete File (Soft Delete)
```
DELETE /api/files/{id}
Headers: Authorization: Bearer {token}
```

### Get Deleted Files
```
GET /api/files/deleted/list
Headers: Authorization: Bearer {token}
```

### Restore Deleted File
```
POST /api/files/{id}/restore
Headers: Authorization: Bearer {token}
```

### Permanently Delete File
```
DELETE /api/files/{id}/permanent
Headers: Authorization: Bearer {token}
```

### Download File
```
GET /api/files/{id}/download
Headers: Authorization: Bearer {token} (optional for public files)
```

Returns file as downloadable attachment.

### Get File Statistics
```
GET /api/files/statistics
Headers: Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_files": 25,
    "total_size": 104857600,
    "by_type": {
      "images": 10,
      "documents": 8,
      "videos": 5,
      "audio": 2
    },
    "total_deleted": 3
  }
}
```

### Get Public Files
```
GET /api/files/public
Headers: Authorization: Bearer {token} (optional)

Query Parameters:
  ?user_id=1             - Filter by specific user
  ?search=filename       - Search by filename
  ?page=1                - Pagination
```

## Storage Configuration

### Local Storage (Default)

Files are stored in `storage/app/public/files/{user_id}/`

Create symbolic link:
```bash
php artisan storage:link
```

Access files at: `http://example.com/storage/files/{user_id}/{filename}`

### S3 Storage

Configure in `.env`:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket
```

Or set via config:
```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'path' => 'files',
]
```

## Usage Examples

### Upload File with Service
```php
use App\Services\FileService;

$fileService = app(FileService::class);

$file = $fileService->upload(
    request()->file('document'),
    auth()->id(),
    'My important document',
    true,  // is_public
    'public'
);

dd($file);
```

### Query Files
```php
use App\Models\File;

// Get all user's image files
$images = File::forUser(auth()->id())->images()->get();

// Get public documents
$documents = File::public()->documents()->paginate(20);

// Get files uploaded by specific user
$files = File::where('uploaded_by', auth()->id())->get();

// Get files by size
$largeFiles = File::where('size', '>', 10485760)->get(); // > 10MB

// Search files
$results = File::where('original_name', 'like', '%report%')
    ->orWhere('description', 'like', '%report%')
    ->get();

// Get total storage used
$totalSize = File::getTotalSize(auth()->id());
```

### File Management
```php
$file = File::find(1);

// Make file public
$file->update(['is_public' => true]);

// Download file
return $fileService->getDownloadStream($file);

// Move to different disk
$fileService->moveFile($file, 's3');

// Delete and restore
$fileService->delete($file);
$fileService->restore($file);

// Permanent deletion
$fileService->forceDelete($file);
```

## Security Considerations

✅ **File Validation** - Files validated for size and MIME type
✅ **Authorization** - Users can only access their own files
✅ **Secure Filenames** - Original names hashed to prevent conflicts
✅ **Private Files** - Private files require authentication and ownership check
✅ **Soft Deletes** - Files can be recovered before permanent deletion
✅ **Storage Security** - Files stored outside web root by default
✅ **Permission Checks** - Integration with role/permission system

## Migrations

Create the files table:
```bash
php artisan migrate
```

This creates the `files` table with proper indexes for efficient querying.

## Performance Tips

1. **Use Scopes** - Leverage query scopes to optimize queries
2. **Paginate** - Use pagination for large file lists
3. **Compression** - Store files with compression enabled
4. **CDN** - Use CDN for frequently accessed public files
5. **Cleanup** - Regularly run cleanup for permanently deleted files
6. **Indexes** - Ensure database indexes are optimized

## Integration with Other Features

### With User Model
```php
$user = User::with('files')->find(1);
$user->files;  // All files owned by user
```

### With Audit Logs
File operations can be logged to AuditLog table for complete tracking:
```php
// Log file upload to audit logs
AuditLog::create([
    'user_id' => auth()->id(),
    'model_type' => File::class,
    'model_id' => $file->id,
    'action' => 'created',
    'description' => 'File uploaded: ' . $file->original_name,
]);
```

### With Notifications
Users can be notified when files are shared or updated:
```php
$user->notify(new FileSharedNotification($file));
```

## Error Handling

Common errors and responses:

**File Too Large:**
```json
{
  "success": false,
  "message": "File size exceeds maximum allowed size of 100 MB"
}
```

**Unsupported MIME Type:**
```json
{
  "success": false,
  "message": "File type not allowed. Allowed types: ..."
}
```

**Unauthorized Access:**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**File Not Found:**
```json
{
  "success": false,
  "message": "File not found on storage"
}
```

## Advanced Features

### Custom File Validation
```php
$fileService
    ->setMaxFileSize(52428800)  // 50 MB
    ->setAllowedMimeTypes(['image/jpeg', 'image/png'])
    ->upload($file, auth()->id());
```

### Batch Upload
```php
$files = request()->file('files');

foreach ($files as $uploadedFile) {
    $fileService->upload($uploadedFile, auth()->id());
}
```

### File Organization
```php
// Query files by upload month
$files = File::where('created_at', '>=', '2026-05-01')
    ->where('created_at', '<=', '2026-05-31')
    ->get();
```

## Troubleshooting

**Storage symlink not working:**
```bash
php artisan storage:link
# or
rm public/storage && ln -s storage/app/public public/storage
```

**Permission issues:**
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

**Files not showing up:**
1. Check database connection
2. Verify user_id is correct
3. Check soft_deletes scope

## Next Steps

1. Run migration: `php artisan migrate`
2. Create storage symlink: `php artisan storage:link`
3. Test file upload via API
4. Configure S3 for production
5. Set up cleanup jobs for old deleted files
6. Monitor storage usage

