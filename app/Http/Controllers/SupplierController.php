<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index()
    {
        try {
            $suppliers = Supplier::active()->orderBy('supplier_name', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $suppliers,
                'message' => 'Suppliers fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch suppliers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all suppliers (including inactive).
     */
    public function getAll()
    {
        try {
            $suppliers = Supplier::orderBy('supplier_name', 'asc')->get();

            // dd($suppliers);

            return response()->json([
                'status' => 'success',
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier getAll error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch suppliers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'entrydate' => 'nullable|date',
                'supplier_name' => 'required|string|max:200|unique:suppliersetup,supplier_name',
                'address' => 'nullable|string',
                'contact_no' => 'required|string|max:50',
                'username' => 'required|string|max:100',
                'bin_nid' => 'nullable|string|max:150',
                'ope_balance' => 'nullable|numeric',
                'adv_balance' => 'nullable|numeric',
                'due_balance' => 'nullable|numeric',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $supplier = Supplier::create([
                'entrydate' => $validated['entrydate'] ?? now()->format('Y-m-d'),
                'supplier_name' => $validated['supplier_name'],
                'address' => $validated['address'],
                'contact_no' => $validated['contact_no'],
                'username' => $validated['username'],
                'bin_nid' => $validated['bin_nid'],
                'ope_balance' => $validated['ope_balance'] ?? 0,
                'adv_balance' => $validated['adv_balance'] ?? 0,
                'due_balance' => $validated['due_balance'] ?? 0,
                'validity' => $validated['validity'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier created successfully!',
                'data' => $supplier,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Supplier store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified supplier.
     */
    public function show($id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, $id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found'
                ], 404);
            }

            $validated = $request->validate([
                'entrydate' => 'nullable|date',
                'supplier_name' => 'sometimes|string|max:200|unique:suppliersetup,supplier_name,' . $id,
                'address' => 'nullable|string',
                'contact_no' => 'sometimes|string|max:50',
                'username' => 'sometimes|string|max:100',
                'bin_nid' => 'nullable|string|max:150',
                'ope_balance' => 'nullable|numeric',
                'adv_balance' => 'nullable|numeric',
                'due_balance' => 'nullable|numeric',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $supplier->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier updated successfully!',
                'data' => $supplier,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Supplier update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified supplier (soft delete).
     */
    public function destroy($id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found'
                ], 404);
            }

            $supplier->validity = 0;
            $supplier->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted supplier.
     */
    public function restore($id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found'
                ], 404);
            }

            $supplier->validity = 1;
            $supplier->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Supplier restored successfully',
                'data' => $supplier
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get supplier ledger.
     */
    public function getLedger($id)
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supplier not found'
                ], 404);
            }

            $ledgers = SupplierLedger::where('supplier_id', $id)
                ->orderBy('entry_date', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'supplier' => $supplier,
                    'ledgers' => $ledgers,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier ledger error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch ledger',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
