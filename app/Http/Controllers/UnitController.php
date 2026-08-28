<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // ✅ Query with integer status (1 = active)
            $units = Unit::where('status', 1)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $units
            ]);
        } catch (\Exception $e) {
            Log::error('Unit index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch units',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // ✅ Validate as integer
            $validated = $request->validate([
                'unit_name' => 'required|string|max:255|unique:unitls,unit_name',
                'status' => 'nullable|integer|in:0,1', // ✅ Integer validation
                'validity' => 'nullable|integer|in:0,1',
            ]);

            // Check if unit already exists
            if (Unit::where('unit_name', $request->unit_name)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit name already exists!',
                ], 409);
            }

            // ✅ Create unit with integer status
            $unit = Unit::create([
                'unit_name' => $validated['unit_name'],
                'status' => $validated['status'] ?? 1, // 1 = Active, 0 = Inactive
                'validity' => $validated['validity'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unit created successfully!',
                'data' => $unit,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unit store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $unit
            ]);
        } catch (\Exception $e) {
            Log::error('Unit show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'unit_name' => 'sometimes|string|max:255|unique:unitls,unit_name,' . $id,
                'status' => 'nullable|integer|in:0,1',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $unit = Unit::find($id);
            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found'
                ], 404);
            }

            $unit->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Unit updated successfully',
                'data' => $unit
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unit update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (Soft Delete).
     */
    public function destroy(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found'
                ], 404);
            }

            // ✅ Soft delete - set status to 0 (inactive)
            $unit->status = 0;
            $unit->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Unit delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted unit.
     */
    public function restore(string $id)
    {
        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit not found'
                ], 404);
            }

            // ✅ Restore - set status to 1 (active)
            $unit->status = 1;
            $unit->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit restored successfully',
                'data' => $unit
            ]);
        } catch (\Exception $e) {
            Log::error('Unit restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all units (including inactive).
     */
    public function getAll()
    {
        try {
            $units = Unit::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $units
            ]);
        } catch (\Exception $e) {
            Log::error('Unit getAll error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch units',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active units only.
     */
    public function getActive()
    {
        try {
            $units = Unit::where('status', 1)
                ->orderBy('unit_name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $units
            ]);
        } catch (\Exception $e) {
            Log::error('Unit getActive error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active units',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
