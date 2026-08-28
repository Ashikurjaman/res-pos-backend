<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\FoodType;
use App\Models\BranchStore;
use App\Models\HeadOfficeStore;
use App\Models\HeadOfficeStock;
use App\Models\SupplierProduct;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Get product creation data (categories, units, suppliers, food_types).
     */
    public function create()
    {
        try {
            // Get next product code
            $lastProduct = Product::orderBy('id', 'desc')->first();
            $lastCode = $lastProduct ? intval(substr($lastProduct->product_code, -5)) : 0;
            $nextCode = str_pad($lastCode + 1, 5, '0', STR_PAD_LEFT);

            // Get categories
            $categories = Category::where('status', 1)->get();

            // Get units
            $units = Unit::where('status', 1)->get();

            // Get suppliers
            $suppliers = Supplier::where('validity', 1)->get();

            // ✅ Get food types from database
            $foodTypes = FoodType::where('validity', 1)
                ->where('onlinestatus', 1)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'next_code' => $nextCode,
                    'categories' => $categories,
                    'units' => $units,
                    'suppliers' => $suppliers,
                    'food_types' => $foodTypes,
                ]
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
                'product_code' => 'required|string|max:115|unique:product,product_code',
                'category_id' => 'required|exists:categories,id',
                'unit_id' => 'required|exists:units,id',
                'food_type_id' => 'nullable|exists:food_types,id', // ✅ Added
                'cost_price' => 'nullable|numeric|min:0',
                'pur_price' => 'nullable|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'opening_balance' => 'nullable|numeric|min:0',
                'vat_rate' => 'nullable|integer|min:0|max:100',
                'sd_rate' => 'nullable|integer|min:0|max:100',
                'scharge' => 'nullable|numeric|min:0',
                'product_type' => 'nullable|integer|in:1,2,3',
                'expire' => 'nullable|string|max:20',
                'supplier_id' => 'nullable|array',
                'supplier_id.*' => 'exists:suppliers,id',
                'outlet_id' => 'nullable|exists:outlets,id',
            ]);

            // Handle product image upload
            $productImage = null;
            if ($request->hasFile('product_image')) {
                $image = $request->file('product_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('products', $filename, 'public');
                $productImage = 'storage/' . $path;
            }

            // Create product
            $product = Product::create([
                'entrydate' => now()->format('Y-m-d'),
                'category_id' => $validated['category_id'],
                'product_name' => $validated['product_name'],
                'product_code' => $validated['product_code'],
                'cost_price' => $validated['cost_price'] ?? 0,
                'pur_price' => $validated['pur_price'] ?? 0,
                'last_price' => $validated['pur_price'] ?? 0,
                'previous_price' => null,
                'avg_price' => $validated['pur_price'] ?? 0,
                'sale_price' => $validated['sale_price'] ?? 0,
                'expire' => $validated['expire'] ?? null,
                'unit_id' => $validated['unit_id'],
                'food_type_id' => $validated['food_type_id'] ?? null, // ✅ Added
                'dis_status' => $validated['dis_status'] ?? null,
                'vat_rate' => $validated['vat_rate'] ?? 0,
                'sd_rate' => $validated['sd_rate'] ?? 0,
                'scharge' => $validated['scharge'] ?? 0,
                'product_type' => $validated['product_type'] ?? 1,
                'product_image' => $productImage,
                'imagepath' => $productImage,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'supplier_id' => $validated['supplier_id'] ? implode(',', $validated['supplier_id']) : null,
                'status' => 1,
                'user_id' => auth()->id(),
                'validity' => 1,
            ]);

            // Handle opening balance in head office store
            if (($validated['opening_balance'] ?? 0) > 0) {
                HeadOfficeStore::create([
                    'product_id' => $product->id,
                    'entrydate' => now()->format('Y-m-d'),
                    'balanceinhand' => $validated['opening_balance'],
                    'stockbalancebefore' => 0,
                    'stockbalanceafter' => $validated['opening_balance'],
                    'opening_balance' => $validated['opening_balance'],
                    'status' => 1,
                    'validity' => 1,
                ]);
            }

            // Handle branch store
            $outletId = $validated['outlet_id'] ?? 1;
            BranchStore::create([
                'product_id' => $product->id,
                'entrydate' => now()->format('Y-m-d'),
                'balanceinhand' => $validated['opening_balance'] ?? 0,
                'stockbalancebefore' => 0,
                'stockbalanceafter' => $validated['opening_balance'] ?? 0,
                'sale_price' => $validated['sale_price'] ?? 0,
                'vat_rate' => $validated['vat_rate'] ?? 0,
                'sd_rate' => $validated['sd_rate'] ?? 0,
                'scharge' => $validated['scharge'] ?? 0,
                'outlet_id' => $outletId,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'food_type' => $validated['food_type_id'] ?? null,
                'status' => 1,
                'validity' => 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully!',
                'data' => $product,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
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
            $products = Product::with(['category', 'unit', 'foodType', 'suppliers'])
                ->active()
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $products
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
            $product = Product::with(['category', 'unit', 'foodType', 'suppliers', 'branchStores', 'headOfficeStore'])
                ->find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $product
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
                'product_name' => 'sometimes|string|max:255',
                'product_code' => 'sometimes|string|max:115|unique:product,product_code,' . $id,
                'category_id' => 'sometimes|exists:categories,id',
                'unit_id' => 'sometimes|exists:units,id',
                'food_type_id' => 'nullable|exists:food_types,id', // ✅ Added
                'cost_price' => 'nullable|numeric|min:0',
                'pur_price' => 'nullable|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'opening_balance' => 'nullable|numeric|min:0',
                'vat_rate' => 'nullable|integer|min:0|max:100',
                'sd_rate' => 'nullable|integer|min:0|max:100',
                'scharge' => 'nullable|numeric|min:0',
                'product_type' => 'nullable|integer|in:1,2,3',
                'status' => 'nullable|integer|in:0,1',
                'validity' => 'nullable|integer|in:0,1',
            ]);

            // Handle product image upload
            if ($request->hasFile('product_image')) {
                if ($product->product_image) {
                    $oldPath = str_replace('storage/', '', $product->product_image);
                    Storage::disk('public')->delete($oldPath);
                }

                $image = $request->file('product_image');
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('products', $filename, 'public');
                $validated['product_image'] = 'storage/' . $path;
                $validated['imagepath'] = 'storage/' . $path;
            }

            $product->update($validated);

            // Update suppliers
            if (isset($validated['supplier_id'])) {
                $product->suppliers()->sync($validated['supplier_id']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully!',
                'data' => $product,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
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

            if ($product->product_image) {
                $oldPath = str_replace('storage/', '', $product->product_image);
                Storage::disk('public')->delete($oldPath);
            }

            $product->branchStores()->delete();
            $product->headOfficeStore()->delete();
            $product->suppliers()->detach();
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
     * Restore a soft-deleted product.
     */
    public function restore($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            $product->status = 1;
            $product->validity = 1;
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Product restored successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Product restore error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products by category.
     */
    public function getProductsByCategory(Request $request)
    {
        try {
            $categoryId = $request->category_id;
            $outletId = $request->outlet_id ?? 1;

            $products = Product::where('category_id', $categoryId)
                ->where('status', 1)
                ->where('validity', 1)
                ->with(['foodType', 'unit'])
                ->get();

            $productsWithStock = $products->map(function ($product) use ($outletId) {
                $branchStore = BranchStore::where('product_id', $product->id)
                    ->where('outlet_id', $outletId)
                    ->first();

                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'product_code' => $product->product_code,
                    'price' => $product->sale_price ?? $product->pur_price ?? 0,
                    'stock' => $branchStore ? $branchStore->balanceinhand : 0,
                    'vat_rate' => $product->vat_rate ?? 0,
                    'sd_rate' => $product->sd_rate ?? 0,
                    'category' => $product->category_id,
                    'unit_id' => $product->unit_id,
                    'food_type_id' => $product->food_type_id,
                    'food_type_name' => $product->foodType ? $product->foodType->type_name : null,
                    'product_image' => $product->product_image,
                ];
            });

            return response()->json($productsWithStock);
        } catch (\Exception $e) {
            Log::error('Get products by category error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
