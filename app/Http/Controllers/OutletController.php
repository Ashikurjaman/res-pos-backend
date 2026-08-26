<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OutletController extends Controller
{
    /**
     * Display a listing of the outlets.
     */
    public function index()
    {
        try {
            $outlets = Outlet::where('status', 1)
                ->orderBy('outlet_name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $outlets,
                'message' => 'Outlets fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Outlet index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch outlets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all outlets (including inactive).
     */
    public function getAll()
    {
        try {
            $outlets = Outlet::orderBy('outlet_name', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $outlets,
                'message' => 'All outlets fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Outlet getAll error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch outlets',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created outlet.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'entrydate' => 'nullable|date',
                'outlet_code' => 'required|string|max:50|unique:outlets,outlet_code',
                'outlet_name' => 'required|string|max:255',
                'short_name' => 'nullable|string|max:30',
                'outlet_address' => 'required|string|max:255',
                'outlet_mgr' => 'required|string|max:100',
                'mgr_contact_no' => 'required|string|max:111',
                'ho_mobile_no' => 'required|string|max:111',
                'status' => 'nullable|integer|in:0,1',
                'vat_reg_no_old' => 'nullable|string|max:111',
                'vat_reg_no_new' => 'nullable|string|max:111',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            // Check if outlet code already exists
            if (Outlet::where('outlet_code', $request->outlet_code)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outlet code already exists!',
                ], 409);
            }

            $outlet = Outlet::create([
                'entrydate' => $validated['entrydate'] ?? now()->format('Y-m-d'),
                'outlet_code' => $validated['outlet_code'],
                'outlet_name' => $validated['outlet_name'],
                'short_name' => $validated['short_name'] ?? null,
                'outlet_address' => $validated['outlet_address'],
                'outlet_mgr' => $validated['outlet_mgr'],
                'mgr_contact_no' => $validated['mgr_contact_no'],
                'ho_mobile_no' => $validated['ho_mobile_no'],
                'status' => $validated['status'] ?? 1,
                'vat_reg_no_old' => $validated['vat_reg_no_old'] ?? null,
                'vat_reg_no_new' => $validated['vat_reg_no_new'] ?? null,
                'validity' => $validated['validity'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Outlet created successfully!',
                'data' => $outlet,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Outlet store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create outlet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified outlet.
     */
    public function show(string $id)
    {
        try {
            $outlet = Outlet::find($id);

            if (!$outlet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outlet not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $outlet,
                'message' => 'Outlet fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Outlet show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch outlet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified outlet.
     */
    public function update(Request $request, string $id)
    {
        try {
            $outlet = Outlet::find($id);

            if (!$outlet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outlet not found'
                ], 404);
            }

            $validated = $request->validate([
                'entrydate' => 'nullable|date',
                'outlet_code' => 'sometimes|string|max:50|unique:outlets,outlet_code,' . $id,
                'outlet_name' => 'sometimes|string|max:255',
                'short_name' => 'nullable|string|max:30',
                'outlet_address' => 'sometimes|string|max:255',
                'outlet_mgr' => 'sometimes|string|max:100',
                'mgr_contact_no' => 'sometimes|string|max:111',
                'ho_mobile_no' => 'sometimes|string|max:111',
                'status' => 'nullable|integer|in:0,1',
                'vat_reg_no_old' => 'nullable|string|max:111',
                'vat_reg_no_new' => 'nullable|string|max:111',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $outlet->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Outlet updated successfully!',
                'data' => $outlet,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Outlet update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update outlet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified outlet (soft delete).
     */
    public function destroy(string $id)
    {
        try {
            $outlet = Outlet::find($id);

            if (!$outlet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outlet not found'
                ], 404);
            }

            // Soft delete - set status to 0
            $outlet->status = 0;
            $outlet->validity = 0;
            $outlet->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Outlet deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Outlet delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete outlet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted outlet.
     */
    public function restore(string $id)
    {
        try {
            $outlet = Outlet::find($id);

            if (!$outlet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Outlet not found'
                ], 404);
            }

            $outlet->status = 1;
            $outlet->validity = 1;
            $outlet->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Outlet restored successfully',
                'data' => $outlet
            ], 200);
        } catch (\Exception $e) {
            Log::error('Outlet restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore outlet',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active outlets.
     */
    public function getActive()
    {
        try {
            $outlets = Outlet::where('status', 1)
                ->where('validity', 1)
                ->orderBy('outlet_name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $outlets,
                'message' => 'Active outlets fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Outlet getActive error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active outlets',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
