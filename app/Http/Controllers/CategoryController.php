<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // ✅ Fix: Use integer status (1 = active, 0 = inactive)
            $categories = Category::where('status', 1)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
                'message' => 'Categories fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Category index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories',
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
            $validated = $request->validate([
                'category_name' => 'required|string|max:255|unique:category_models,category_name',
                'status' => 'nullable|integer|in:0,1',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            // Check if category already exists
            if (Category::where('category_name', $request->category_name)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category name already exists!',
                ], 409);
            }

            $category = Category::create([
                'category_name' => $validated['category_name'],
                'status' => $validated['status'] ?? 1,
                'validity' => $validated['validity'] ?? 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Category created successfully!',
                'data' => $category,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Category store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create category',
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
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $category,
                'message' => 'Category fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Category show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch category',
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
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404);
            }

            $validated = $request->validate([
                'category_name' => 'sometimes|string|max:255|unique:category_models,category_name,' . $id,
                'status' => 'nullable|integer|in:0,1',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            $category->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Category updated successfully',
                'data' => $category
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Category update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update category',
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
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404);
            }

            // ✅ Soft delete - set status to 0 (inactive)
            $category->status = 0;
            $category->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Category delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all categories (including inactive).
     */
    public function getAll()
    {
        try {
            $categories = Category::orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
                'message' => 'All categories fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Category getAll error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories with active status only.
     */
    public function getActive()
    {
        try {
            $categories = Category::where('status', 1)
                ->orderBy('category_name', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
                'message' => 'Active categories fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Category getActive error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted category.
     */
    public function restore(string $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404);
            }

            $category->status = 1;
            $category->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Category restored successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            Log::error('Category restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
