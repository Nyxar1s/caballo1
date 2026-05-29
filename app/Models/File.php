<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'size',
        'extension',
        'disk',
        'description',
        'is_public',
        'uploaded_by',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_public' => 'boolean',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be appended.
     *
     * @var array<int, string>
     */
    protected $appends = ['file_url', 'human_readable_size'];

    /**
     * Get the user that uploaded this file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who uploaded this file.
     */
    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    /**
     * Get the file URL attribute.
     */
    public function getFileUrlAttribute(): string
    {
        if ($this->is_public) {
            return url("storage/{$this->file_path}");
        }
        return route('files.download', $this->id);
    }

    /**
     * Get human readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $i < count($units) - 1; $i++) {
            if ($bytes < 1024) {
                return round($bytes, 2) . ' ' . $units[$i];
            }
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[count($units) - 1];
    }

    /**
     * Scope: Get files for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get public files.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: Get private files.
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope: Get files by mime type.
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', 'like', $mimeType . '%');
    }

    /**
     * Scope: Get image files.
     */
    public function scopeImages($query)
    {
        return $query->byMimeType('image');
    }

    /**
     * Scope: Get document files.
     */
    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Scope: Get video files.
     */
    public function scopeVideos($query)
    {
        return $query->byMimeType('video');
    }

    /**
     * Scope: Get audio files.
     */
    public function scopeAudio($query)
    {
        return $query->byMimeType('audio');
    }

    /**
     * Get total size of files.
     */
    public static function getTotalSize(int $userId): int
    {
        return self::forUser($userId)->sum('size');
    }
}
