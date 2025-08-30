<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lastProduct = Product::orderBy('id', 'desc')->first();

        if ($lastProduct) {
            // Example: if last product_code = P100 → next = P101
            $number = (int) filter_var($lastProduct->product_code, FILTER_SANITIZE_NUMBER_INT);
            $nextCode = ($number + 1);
        } else {
            // First product → default P1001
            $nextCode = '1';
        }

        return response()->json([
            'status' => 'success',
            'next_code' => (string) $nextCode,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        // Validate input
        $request->validate([
            'product_name' => 'required|string',
            'category' => 'required|string',
            'product_type' => 'required|string',
            'price' => 'required|numeric',
            'product_code' => 'required|string',
            'unit' => 'required|string',
            'vat' => 'nullable|numeric',
            'sd' => 'nullable|numeric',
        ]);
        if (Product::where('product_code', $request->product_code)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product code already exists!',
            ], 409); // 409 Conflict
        }

        // Create product
        $product = Product::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully!',
            'data' => $product,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
