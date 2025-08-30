<?php

namespace App\Http\Controllers;

use App\Models\CategoryModel;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $category = CategoryModel::where('status', '=', 1)->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($category);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //


    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'category_name' => 'required|string',
            'status' => 'required|string',
        ]);
        if (CategoryModel::where('category_name', $request->category_name)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category name already exists!',
            ], 409); // 409 Conflict
        }

        // Create product
        $category = CategoryModel::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully!',
            'data' => $category,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //

        $category = CategoryModel::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'status'     => 'required',
        ]);

        // Find product
        $category = CategoryModel::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Update product
        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = CategoryModel::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->status = 0;
        $category->save();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
