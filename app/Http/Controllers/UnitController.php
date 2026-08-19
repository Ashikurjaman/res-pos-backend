<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $units = Unit::where('status', 1)->orderBy('created_at', 'asc')->get();
            
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
            $request->validate([
                'unit_name' => 'required|string|max:255|unique:unitls,unit_name',
                'status' => 'nullable|string',
            ]);

            // Check if unit already exists
            if (Unit::where('unit_name', $request->unit_name)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit name already exists!',
                ], 409);
            }

            // Create unit
            $unit = Unit::create([
                'unit_name' => $request->unit_name,
                'status' => $request->status ?? '1',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Unit created successfully!',
                'data' => $unit,
            ], 201);
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
                'unit_name' => 'required|string|max:255|unique:unitls,unit_name,' . $id,
                'status' => 'required|string',
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
     * Remove the specified resource from storage.
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
}