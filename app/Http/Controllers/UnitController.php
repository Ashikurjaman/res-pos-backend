<?php

namespace App\Http\Controllers;

use App\Models\Unitl;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $unit = Unitl::where('status', '=', 1)->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($unit);
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
            'unit_name' => 'required|string',
            'status' => 'required',
        ]);
        if (Unitl::where('unit_name', $request->unit_name)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit name already exists!',
            ], 409); // 409 Conflict
        }

        // Create product
        $category = Unitl::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Unit created successfully!',
            'data' => $category,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $unit = Unitl::findOrFail($id);
        return response()->json($unit);
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
            'unit_name' => 'required|string|max:255',
            'status'     => 'required',
        ]);

        // Find product
        $unit = Unitl::find($id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        // Update product
        $unit->update($validated);

        return response()->json([
            'message' => 'Unit updated successfully',
            'data' => $unit
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $unit = Unitl::find($id);

        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        $unit->status = 0;
        $unit->save();

        return response()->json(['message' => 'Unit deleted successfully']);
    }
}
