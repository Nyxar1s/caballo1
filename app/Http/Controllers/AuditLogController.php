<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Get all audit logs with pagination.
     */
    public function index(Request $request)
    {
        $query = AuditLog::query();

        // Filter by model type
        if ($request->has('model_type')) {
            $query->forModel($request->input('model_type'));
        }

        // Filter by action
        if ($request->has('action')) {
            $query->forAction($request->input('action'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $logs = $query->with(['user' => function ($q) {
            $q->select('id', 'name', 'email');
        }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }

    /**
     * Get audit logs for a specific model instance.
     */
    public function forModel(Request $request, string $modelType, string $modelId)
    {
        $logs = AuditLog::forModelId($modelType, (int)$modelId)
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }

    /**
     * Get audit logs for a specific user.
     */
    public function userLogs(User $user)
    {
        $logs = $user->auditLogs()
            ->with(['user' => function ($q) {
                $q->select('id', 'name', 'email');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ], 200);
    }

    /**
     * Get a single audit log.
     */
    public function show(AuditLog $auditLog)
    {
        return response()->json([
            'success' => true,
            'data' => $auditLog->load('user'),
        ], 200);
    }

    /**
     * Get audit logs statistics.
     */
    public function statistics(Request $request)
    {
        $days = $request->input('days', 30);

        $stats = [
            'total_logs' => AuditLog::recentDays($days)->count(),
            'by_action' => AuditLog::recentDays($days)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->get(),
            'by_model_type' => AuditLog::recentDays($days)
                ->selectRaw('model_type, COUNT(*) as count')
                ->groupBy('model_type')
                ->get(),
            'by_user' => AuditLog::recentDays($days)
                ->with(['user' => function ($q) {
                    $q->select('id', 'name', 'email');
                }])
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }
}
