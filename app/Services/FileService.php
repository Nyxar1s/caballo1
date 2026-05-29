<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Maximum file size in bytes (100 MB by default).
     */
    private int $maxFileSize = 104857600;

    /**
     * Allowed MIME types.
     */
    private array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'video/mp4',
        'video/mpeg',
        'audio/mpeg',
        'audio/wav',
    ];

    /**
     * Upload a file.
     *
     * @return File|null
     */
    public function upload(UploadedFile $uploadedFile, int $userId, ?string $description = null, bool $isPublic = true, ?string $disk = 'public'): ?File
    {
        try {
            // Validate file
            $this->validateFile($uploadedFile);

            // Generate unique filename
            $fileName = $this->generateFileName($uploadedFile);
            
            // Store file
            $path = Storage::disk($disk)->putFileAs(
                'files/' . $userId,
                $uploadedFile,
                $fileName
            );

            // Create file record
            $file = File::create([
                'user_id' => $userId,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_name' => $fileName,
                'file_path' => $path,
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'extension' => $uploadedFile->getClientOriginalExtension(),
                'disk' => $disk,
                'description' => $description,
                'is_public' => $isPublic,
                'uploaded_by' => auth()->id(),
                'metadata' => [
                    'original_extension' => $uploadedFile->getClientOriginalExtension(),
                    'original_size' => $uploadedFile->getSize(),
                    'uploaded_from' => request()->ip(),
                ],
            ]);

            return $file;
        } catch (\Exception $e) {
            throw new \Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file.
     */
    public function delete(File $file): bool
    {
        try {
            // Delete from storage
            if (Storage::disk($file->disk)->exists($file->file_path)) {
                Storage::disk($file->disk)->delete($file->file_path);
            }

            // Delete from database (soft delete)
            $file->delete();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('File deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a file.
     */
    public function forceDelete(File $file): bool
    {
        try {
            // Delete from storage
            if (Storage::disk($file->disk)->exists($file->file_path)) {
                Storage::disk($file->disk)->delete($file->file_path);
            }

            // Permanently delete from database
            $file->forceDelete();

            return true;
        } catch (\Exception $e) {
            throw new \Exception('File permanent deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted file.
     */
    public function restore(File $file): bool
    {
        try {
            $file->restore();
            return true;
        } catch (\Exception $e) {
            throw new \Exception('File restoration failed: ' . $e->getMessage());
        }
    }

    /**
     * Update file metadata.
     */
    public function update(File $file, array $data): File
    {
        $allowedFields = ['description', 'is_public'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $file->$field = $data[$field];
            }
        }

        $file->save();
        return $file;
    }

    /**
     * Move file to another disk.
     */
    public function moveFile(File $file, string $newDisk): File
    {
        try {
            // Read from old disk
            $content = Storage::disk($file->disk)->get($file->file_path);

            // Write to new disk
            $newPath = Storage::disk($newDisk)->putFileAs(
                'files/' . $file->user_id,
                $file->file_name,
                $content
            );

            // Delete from old disk
            Storage::disk($file->disk)->delete($file->file_path);

            // Update file record
            $file->update([
                'file_path' => $newPath,
                'disk' => $newDisk,
            ]);

            return $file;
        } catch (\Exception $e) {
            throw new \Exception('File move failed: ' . $e->getMessage());
        }
    }

    /**
     * Get file download stream.
     */
    public function getDownloadStream(File $file)
    {
        if (!Storage::disk($file->disk)->exists($file->file_path)) {
            throw new \Exception('File not found on storage');
        }

        return Storage::disk($file->disk)->download($file->file_path, $file->original_name);
    }

    /**
     * Validate uploaded file.
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception('File size exceeds maximum allowed size of ' . $this->getReadableSize($this->maxFileSize));
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \Exception('File type not allowed. Allowed types: ' . implode(', ', $this->allowedMimeTypes));
        }
    }

    /**
     * Generate unique filename.
     */
    private function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        return Str::slug($baseName) . '_' . Str::random(10) . '.' . $extension;
    }

    /**
     * Get readable file size.
     */
    private function getReadableSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Set maximum file size.
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    /**
     * Set allowed MIME types.
     */
    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }

    /**
     * Add allowed MIME type.
     */
    public function addAllowedMimeType(string $mimeType): self
    {
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            $this->allowedMimeTypes[] = $mimeType;
        }
        return $this;
    }
}
