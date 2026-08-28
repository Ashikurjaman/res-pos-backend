<?php

namespace App\Http\Controllers;

use App\Models\FoodType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FoodTypeController extends Controller
{
    /**
     * Display a listing of food types.
     */
    public function index()
    {
        try {
            $foodTypes = FoodType::withCount('products')
                ->orderBy('type_name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $foodTypes,
                'message' => 'Food types fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('FoodType index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch food types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active food types only.
     */
    public function getActive()
    {
        try {
            $foodTypes = FoodType::active()
                ->online()
                ->orderBy('type_name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $foodTypes,
                'message' => 'Active food types fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('FoodType getActive error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active food types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created food type.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type_name' => 'required|string|max:100|unique:food_types,type_name',
                'printer_ip' => 'nullable|string|max:50',
                'onlinestatus' => 'nullable|integer|in:0,1',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $foodType = FoodType::create([
                'type_name' => $validated['type_name'],
                'printer_ip' => $validated['printer_ip'] ?? null,
                'onlinestatus' => $validated['onlinestatus'] ?? 1,
                'validity' => $validated['validity'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Food type created successfully!',
                'data' => $foodType,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('FoodType store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create food type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified food type.
     */
    public function show($id)
    {
        try {
            $foodType = FoodType::with('products')->find($id);

            if (!$foodType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Food type not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $foodType,
                'message' => 'Food type fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('FoodType show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch food type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified food type.
     */
    public function update(Request $request, $id)
    {
        try {
            $foodType = FoodType::find($id);

            if (!$foodType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Food type not found'
                ], 404);
            }

            $validated = $request->validate([
                'type_name' => 'sometimes|string|max:100|unique:food_types,type_name,' . $id,
                'printer_ip' => 'nullable|string|max:50',
                'onlinestatus' => 'nullable|integer|in:0,1',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $foodType->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Food type updated successfully!',
                'data' => $foodType,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('FoodType update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update food type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified food type (soft delete).
     */
    public function destroy($id)
    {
        try {
            $foodType = FoodType::find($id);

            if (!$foodType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Food type not found'
                ], 404);
            }

            // Check if food type has products
            if ($foodType->products()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete food type with associated products',
                    'products_count' => $foodType->products()->count()
                ], 409);
            }

            // Soft delete - set validity to 0
            $foodType->validity = 0;
            $foodType->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Food type deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('FoodType delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete food type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted food type.
     */
    public function restore($id)
    {
        try {
            $foodType = FoodType::find($id);

            if (!$foodType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Food type not found'
                ], 404);
            }

            $foodType->validity = 1;
            $foodType->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Food type restored successfully',
                'data' => $foodType
            ]);
        } catch (\Exception $e) {
            Log::error('FoodType restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore food type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle online status.
     */
    public function toggleOnline($id)
    {
        try {
            $foodType = FoodType::find($id);

            if (!$foodType) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Food type not found'
                ], 404);
            }

            $foodType->onlinestatus = $foodType->onlinestatus == 1 ? 0 : 1;
            $foodType->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Food type status toggled successfully',
                'data' => $foodType
            ]);
        } catch (\Exception $e) {
            Log::error('FoodType toggleOnline error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
