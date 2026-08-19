<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\BranchStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Get next product code with categories and units.
     */
    public function create()
    {
        try {
            // Get next product code
            $lastProduct = Product::orderBy('id', 'desc')->first();
            $lastCode = $lastProduct ? intval(substr($lastProduct->product_code, -5)) : 0;
            $nextCode = str_pad($lastCode + 1, 5, '0', STR_PAD_LEFT);

            // Get all categories from category_models table
            $categories = Category::where('status', 1)->get();
            
            // Get all units from unitls table
            $units = Unit::where('status', 1)->get();

            return response()->json([
                'next_code' => $nextCode,
                'categories' => $categories,
                'units' => $units
            ]);
        } catch (\Exception $e) {
            Log::error('Product create error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load product data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'category_id' => 'required|exists:category_models,id',
                'product_type' => 'required|string',
                'price' => 'required|numeric|min:0',
                'product_code' => 'nullable|string|unique:products,product_code',
                'unit' => 'required|exists:unitls,id',
                'vat' => 'nullable|numeric|min:0|max:100',
                'sd' => 'nullable|numeric|min:0|max:100',
            ]);

            // Create product
            $product = Product::create($validated);

            // Get category name
            $category = Category::find($validated['category_id']);
            
            // Create branch store entry with all product details
            BranchStore::create([
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'category_id' => $product->category_id,
                'category_name' => $category ? $category->category_name : '',
                'product_type' => (int) $product->product_type,
                'price' => (int) $product->price,
                'prv_stock' => 0,
                'stock' => 0,
                'after_stock' => 0,
                'product_code' => (int) $product->product_code,
                'unit' => (int) $product->unit,
                'vat' => (int) ($product->vat ?? 0),
                'sd' => (int) ($product->sd ?? 0),
                'status' => 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product,
                'product_code' => $product->product_code
            ], 201);
        } catch (\Exception $e) {
            Log::error('Product store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of products.
     */
    public function index()
    {
        try {
            $products = Product::with(['category', 'unit'])->get();
            
            $categories = Category::where('status', 1)->get();
            $units = Unit::where('status', 1)->get();

            return response()->json([
                'products' => $products,
                'categories' => $categories,
                'units' => $units
            ]);
        } catch (\Exception $e) {
            Log::error('Product index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show($id)
    {
        try {
            $product = Product::with(['category', 'unit'])->find($id);
            
            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            $categories = Category::where('status', 1)->get();
            $units = Unit::where('status', 1)->get();

            return response()->json([
                'products' => $product,
                'categories' => $categories,
                'units' => $units
            ]);
        } catch (\Exception $e) {
            Log::error('Product show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);
            
            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'category_id' => 'required|exists:category_models,id',
                'product_type' => 'required|string',
                'price' => 'required|numeric|min:0',
                'unit' => 'required|exists:unitls,id',
                'vat' => 'nullable|numeric|min:0|max:100',
                'sd' => 'nullable|numeric|min:0|max:100',
            ]);

            // Update product
            $product->update($validated);

            // Update branch store
            $category = Category::find($validated['category_id']);
            $branchStore = BranchStore::where('product_id', $product->id)->first();
            
            if ($branchStore) {
                $branchStore->update([
                    'product_name' => $product->product_name,
                    'category_id' => $product->category_id,
                    'category_name' => $category ? $category->category_name : '',
                    'product_type' => (int) $product->product_type,
                    'price' => (int) $product->price,
                    'product_code' => (int) $product->product_code,
                    'unit' => (int) $product->unit,
                    'vat' => (int) ($product->vat ?? 0),
                    'sd' => (int) ($product->sd ?? 0),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Product update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        try {
            $product = Product::find($id);
            
            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            // Delete branch store entry
            BranchStore::where('product_id', $product->id)->delete();

            $product->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Product delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
 * Get products for a specific category (for sale page).
 */
public function getProduct(Request $request)
{
    try {
        $categoryId = $request->category_id;
        
        // Get products by category
        $products = Product::where('category_id', $categoryId)->get();
        
        // ✅ Add stock information from branch store
        $productsWithStock = $products->map(function ($product) {
            // Get branch store stock
            $branchStore = BranchStore::where('product_id', $product->id)->first();
            
            return [
                'id' => $product->id,
                'product_name' => $product->product_name,
                'price' => (float) $product->price,
                'stock' => $branchStore ? (int) $branchStore->stock : 0,
                'vat' => (float) ($product->vat ?? 0),
                'sd' => (float) ($product->sd ?? 0),
                'category' => $product->category_id,
            ];
        });
        
        return response()->json($productsWithStock);
    } catch (\Exception $e) {
        Log::error('Get product error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch products',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Get product stock from branch store.
     */
    public function getStock($id)
    {
        try {
            $branchStore = BranchStore::where('product_id', $id)->first();
            
            if (!$branchStore) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found in branch store'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'product_id' => $branchStore->product_id,
                    'product_name' => $branchStore->product_name,
                    'stock' => $branchStore->stock,
                    'prv_stock' => $branchStore->prv_stock,
                    'after_stock' => $branchStore->after_stock,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get stock error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product stock in branch store.
     */
    public function updateStock(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'stock' => 'required|numeric|min:0',
            ]);

            $branchStore = BranchStore::where('product_id', $id)->first();
            
            if (!$branchStore) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found in branch store'
                ], 404);
            }

            $branchStore->prv_stock = $branchStore->stock;
            $branchStore->stock = $validated['stock'];
            $branchStore->after_stock = $validated['stock'];
            $branchStore->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock updated successfully',
                'data' => $branchStore
            ]);
        } catch (\Exception $e) {
            Log::error('Update stock error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all products with their stock from branch store.
     */
    /**
 * Get all products with their stock from branch store.
 */
/**
 * Get all products with their stock from branch store.
 */
public function getProductsWithStock()
{
    try {
        // Get all products
        $products = Product::all();
        
        $productsWithStock = [];
        
        foreach ($products as $product) {
            // Get category
            $category = Category::find($product->category_id);
            
            // Get unit
            $unit = Unit::find($product->unit);
            
            // Get branch store
            $branchStore = BranchStore::where('product_id', $product->id)->first();
            
            $productsWithStock[] = [
                'id' => $product->id,
                'product_name' => $product->product_name ?? '',
                'product_code' => $product->product_code ?? '',
                'price' => (float) ($product->price ?? 0),
                'category_name' => $category ? $category->category_name : '',
                'unit_name' => $unit ? $unit->unit_name : '',
                'stock' => (int) ($branchStore ? $branchStore->stock : 0),
                'prv_stock' => (int) ($branchStore ? $branchStore->prv_stock : 0),
                'after_stock' => (int) ($branchStore ? $branchStore->after_stock : 0),
                'vat' => (float) ($product->vat ?? 0),
                'sd' => (float) ($product->sd ?? 0),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $productsWithStock
        ]);
    } catch (\Exception $e) {
        Log::error('Get products with stock error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch products with stock',
            'error' => $e->getMessage()
        ], 500);
    }
}
}