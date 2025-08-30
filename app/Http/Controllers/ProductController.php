<?php

namespace App\Http\Controllers;

use App\Models\BranchStore;
use App\Models\CategoryModel;
use App\Models\Product;
use App\Models\Unitl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $products = Product::all(); // fetch all products
        $products = Product::where('status', '=', 1)->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($products);
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
        $categories = CategoryModel::select('id', 'category_name')->get();
        $unit = Unitl::select('id', 'unit_name')->get();
        return response()->json([
            'status' => 'success',
            'next_code' => (string) $nextCode,
            'category' => $categories,
            'units' => $unit,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());

        // Validate input
        $request->validate([
            'product_name' => 'required|string',
            'category_id' => 'required',
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

        try {
            //code...
            DB::beginTransaction();

            $product = Product::create([
                'product_name' => $request->product_name,
                'category_id' => $request->category_id,
                'product_type' => $request->product_type,
                'price' => $request->price,
                'product_code' => $request->product_code,
                'unit' => $request->unit,
                'vat' => $request->vat,
                'sd' => $request->sd,
            ]);

            DB::table('branch_stores')->insert([
                'product_id'    => $product->id,
                'product_name'  => $product->product_name,
                'category_id'   => $request->category_id,
                'category_name' => $request->category_name,
                'product_type'  => $product->product_type,
                'price'         => $product->price,
                'stock'         => 0,
                'product_code'  => $product->product_code,
                'unit'          => $product->unit,
                'vat'           => $product->vat,
                'sd'            => $product->sd,
                'status'        => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully!',
                'data' => $product,
            ], 201);
        } catch (\Throwable $e) {
            //throw $th;
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $product = Product::findOrFail($id);
        return response()->json($product);
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
        // Validate input
        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'category'     => 'required',
            'product_type' => 'required',
            'price'        => 'required|numeric',
            'product_code' => 'required',
            'unit'         => 'required',
            'vat'          => 'nullable|numeric',
            'sd'           => 'nullable|numeric',
        ]);

        // Find product
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Update product
        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->status = 0;
        $product->save();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
