<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     */
    public function index()
    {
        try {
            $companies = Company::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $companies,
                'message' => 'Companies fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Company index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch companies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created company.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'company_name' => 'required|string|max:255',
                'outlet_name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'contact_no' => 'required|string|max:255',
                'email' => 'nullable|email|max:155',
                'slogan' => 'required|string|max:255',
                'pay_type' => 'nullable|integer|in:1,2',
                'validity' => 'nullable|boolean',
            ]);

            // Check if company already exists
            if (Company::where('company_name', $request->company_name)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company name already exists!',
                ], 409);
            }

            $company = Company::create([
                'company_name' => $validated['company_name'],
                'outlet_name' => $validated['outlet_name'],
                'address' => $validated['address'],
                'contact_no' => $validated['contact_no'],
                'email' => $validated['email'] ?? null,
                'slogan' => $validated['slogan'],
                'pay_type' => $validated['pay_type'] ?? 1,
                'validity' => $validated['validity'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Company created successfully!',
                'data' => $company,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Company store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified company.
     */
    public function show(string $id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $company,
                'message' => 'Company fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Company show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified company.
     */
    public function update(Request $request, string $id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company not found'
                ], 404);
            }

            $validated = $request->validate([
                'company_name' => 'sometimes|string|max:255',
                'outlet_name' => 'sometimes|string|max:255',
                'address' => 'sometimes|string|max:255',
                'contact_no' => 'sometimes|string|max:255',
                'email' => 'nullable|email|max:155',
                'slogan' => 'sometimes|string|max:255',
                'pay_type' => 'nullable|integer|in:1,2',
                'validity' => 'nullable|boolean',
            ]);

            // Check if company name already exists (excluding current)
            if (
                isset($validated['company_name']) &&
                Company::where('company_name', $validated['company_name'])
                ->where('id', '!=', $id)
                ->exists()
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company name already exists!',
                ], 409);
            }

            $company->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Company updated successfully!',
                'data' => $company,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Company update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified company (soft delete).
     */
    public function destroy(string $id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company not found'
                ], 404);
            }

            // Soft delete - set validity to 0
            $company->validity = 0;
            $company->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Company deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Company delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted company.
     */
    public function restore(string $id)
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company not found'
                ], 404);
            }

            $company->validity = 1;
            $company->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Company restored successfully',
                'data' => $company
            ]);
        } catch (\Exception $e) {
            Log::error('Company restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore company',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active companies.
     */
    public function getActive()
    {
        try {
            $companies = Company::where('validity', 1)->get();

            return response()->json([
                'status' => 'success',
                'data' => $companies,
                'message' => 'Active companies fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Company getActive error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active companies',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
