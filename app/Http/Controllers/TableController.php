<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TableController extends Controller
{
    /**
     * Display a listing of tables.
     */
    public function index(Request $request)
    {
        try {
            $query = Table::query()->where('validity', 1);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Search by table number or name
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('table_number', 'LIKE', "%{$search}%")
                      ->orWhere('table_name', 'LIKE', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->sortBy ?? 'id';
            $sortOrder = $request->sortOrder ?? 'asc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->perPage ?? 10;
            $tables = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $tables
            ]);
        } catch (\Exception $e) {
            Log::error('Table index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all tables (without pagination)
     */
    public function getAll()
    {
        try {
            $tables = Table::where('validity', 1)->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $tables
            ]);
        } catch (\Exception $e) {
            Log::error('Table getAll error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table_number' => 'required|string|max:50|unique:tables,table_number',
                'table_name' => 'required|string|max:100',
                'status' => 'nullable|in:available,occupied,reserved'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $table = Table::create([
                'table_number' => $request->table_number,
                'table_name' => $request->table_name,
                'status' => $request->status ?? 'available',
                'validity' => 1
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Table created successfully',
                'data' => $table
            ], 201);
        } catch (\Exception $e) {
            Log::error('Table store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified table.
     */
    public function show($id)
    {
        try {
            $table = Table::where('validity', 1)->find($id);
            
            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Table not found'
                ], 404);
            }

            // Load related data
            $table->load(['activeSales', 'sales' => function ($query) {
                $query->where('validity', 1)->orderBy('created_at', 'desc')->limit(5);
            }]);

            return response()->json([
                'status' => 'success',
                'data' => $table
            ]);
        } catch (\Exception $e) {
            Log::error('Table show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified table.
     */
    public function update(Request $request, $id)
    {
        try {
            $table = Table::find($id);
            
            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Table not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'table_number' => 'nullable|string|max:50|unique:tables,table_number,' . $id,
                'table_name' => 'nullable|string|max:100',
                'status' => 'nullable|in:available,occupied,reserved'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $table->update($request->only(['table_number', 'table_name', 'status']));

            return response()->json([
                'status' => 'success',
                'message' => 'Table updated successfully',
                'data' => $table
            ]);
        } catch (\Exception $e) {
            Log::error('Table update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update table status.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $table = Table::find($id);
            
            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Table not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:available,occupied,reserved'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $oldStatus = $table->status;
            $table->status = $request->status;
            $table->save();

            // Log status change
            Log::info("Table {$table->table_number} status changed from {$oldStatus} to {$table->status}");

            return response()->json([
                'status' => 'success',
                'message' => 'Table status updated successfully',
                'data' => [
                    'table' => $table,
                    'old_status' => $oldStatus,
                    'new_status' => $table->status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Table status update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update table status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update table status
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table_ids' => 'required|array',
                'table_ids.*' => 'required|exists:tables,id',
                'status' => 'required|in:available,occupied,reserved'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updated = Table::whereIn('id', $request->table_ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'status' => 'success',
                'message' => "{$updated} tables updated successfully",
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk status update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified table (soft delete).
     */
    public function destroy($id)
    {
        try {
            $table = Table::find($id);
            
            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Table not found'
                ], 404);
            }

            // Check if table has active sales
            $activeSales = $table->activeSales()->count();
            if ($activeSales > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot delete table with {$activeSales} active sale(s)"
                ], 400);
            }

            // Soft delete
            $table->validity = 0;
            $table->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Table deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Table delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete the specified table.
     */
    public function forceDelete($id)
    {
        try {
            $table = Table::find($id);
            
            if (!$table) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Table not found'
                ], 404);
            }

            // Check if table has sales
            if ($table->sales()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot permanently delete table with existing sales'
                ], 400);
            }

            $table->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Table permanently deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Table force delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get table statistics.
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => Table::where('validity', 1)->count(),
                'available' => Table::where('validity', 1)->where('status', 'available')->count(),
                'occupied' => Table::where('validity', 1)->where('status', 'occupied')->count(),
                'reserved' => Table::where('validity', 1)->where('status', 'reserved')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Table statistics error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}