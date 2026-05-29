<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Get all files for the authenticated user.
     */
    public function index(Request $request)
    {
        $query = File::forUser(auth()->id());

        // Filter by type
        if ($request->has('type') && $request->input('type')) {
            $type = $request->input('type');
            match ($type) {
                'images' => $query->images(),
                'documents' => $query->documents(),
                'videos' => $query->videos(),
                'audio' => $query->audio(),
                'public' => $query->public(),
                'private' => $query->private(),
                default => $query,
            };
        }

        // Filter by MIME type
        if ($request->has('mime_type') && $request->input('mime_type')) {
            $query->where('mime_type', $request->input('mime_type'));
        }

        // Search by filename
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('original_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $files = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $files,
        ], 200);
    }

    /**
     * Upload a file.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB
                'description' => 'sometimes|nullable|string|max:500',
                'is_public' => 'sometimes|boolean',
            ]);

            $uploadedFile = $request->file('file');
            $description = $request->input('description');
            $isPublic = $request->input('is_public', true);

            $file = $this->fileService->upload(
                $uploadedFile,
                auth()->id(),
                $description,
                $isPublic
            );

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => $file,
            ], 201);
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
     * Get a specific file.
     */
    public function show(File $file)
    {
        // Check authorization
        if ($file->user_id !== auth()->id() && !auth()->user()->hasPermission('view.all.files')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $file,
        ], 200);
    }

    /**
     * Update file metadata.
     */
    public function update(Request $request, File $file)
    {
        try {
            // Check authorization
            if ($file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $request->validate([
                'description' => 'sometimes|nullable|string|max:500',
                'is_public' => 'sometimes|boolean',
            ]);

            $file = $this->fileService->update($file, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'File updated successfully',
                'data' => $file,
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
     * Delete a file (soft delete).
     */
    public function destroy(File $file)
    {
        try {
            // Check authorization
            if ($file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $this->fileService->delete($file);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Download a file.
     */
    public function download(File $file)
    {
        try {
            // Check authorization
            if (!$file->is_public && $file->user_id !== auth()->id() && !auth()->user()->hasPermission('download.all.files')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return $this->fileService->getDownloadStream($file);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get file statistics for user.
     */
    public function statistics()
    {
        $userId = auth()->id();
        $files = File::forUser($userId)->withTrashed()->get();

        $stats = [
            'total_files' => $files->count(),
            'total_size' => File::getTotalSize($userId),
            'by_type' => [
                'images' => $files->where('mime_type', 'like', 'image%')->count(),
                'documents' => $files->whereIn('mime_type', [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])->count(),
                'videos' => $files->where('mime_type', 'like', 'video%')->count(),
                'audio' => $files->where('mime_type', 'like', 'audio%')->count(),
            ],
            'total_deleted' => $files->sum(function ($file) {
                return $file->trashed() ? 1 : 0;
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

    /**
     * Get deleted files.
     */
    public function deleted(Request $request)
    {
        $files = File::forUser(auth()->id())
            ->onlyTrashed()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $files,
        ], 200);
    }

    /**
     * Restore a deleted file.
     */
    public function restore(File $file)
    {
        try {
            // Check authorization
            if ($file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $this->fileService->restore($file);

            return response()->json([
                'success' => true,
                'message' => 'File restored successfully',
                'data' => $file,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Permanently delete a file.
     */
    public function permanentlyDelete(File $file)
    {
        try {
            // Check authorization
            if ($file->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $this->fileService->forceDelete($file);

            return response()->json([
                'success' => true,
                'message' => 'File permanently deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get public files.
     */
    public function public(Request $request)
    {
        $files = File::public()
            ->when($request->has('user_id'), function ($query) use ($request) {
                return $query->where('user_id', $request->input('user_id'));
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $search = $request->input('search');
                return $query->where('original_name', 'like', '%' . $search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $files,
        ], 200);
    }
}
