<?php

namespace App\Http\Controllers;

use App\Models\BranchStore;
use App\Models\Sale;
use App\Models\Saledetails;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $formDate = $request->formDate;
            $toDate = $request->toDate;
            $invoiceNo = $request->invoiceNo;

            $formDateFormat = $formDate ? date('Y-m-d', strtotime($formDate)) : null;
            $toDateFormat = $toDate ? date('Y-m-d', strtotime($toDate)) : null;

            $query = DB::table('sales')
                ->leftJoin('saledetails', 'saledetails.sale_id', '=', 'sales.id')
                ->leftJoin('tables', 'tables.id', '=', 'sales.table_id')
                ->select(
                    'sales.id as sale_id',
                    'sales.entryDate',
                    'sales.invoiceNo',
                    'sales.discount',
                    'sales.paymentMode',
                    'sales.validity',
                    'sales.status as sale_status',
                    'sales.table_id',
                    'tables.table_name',
                    'tables.table_number',
                    'saledetails.product_name',
                    'saledetails.quantity',
                    'saledetails.price',
                    'saledetails.sd',
                    'saledetails.vat',
                    'saledetails.total as item_total'
                );

            if ($formDateFormat && $toDateFormat) {
                $query->whereBetween('sales.entryDate', [$formDateFormat, $toDateFormat]);
            }

            if (!empty($invoiceNo)) {
                $query->where('sales.invoiceNo', $invoiceNo);
            }

            $rows = $query->where('sales.validity', 1)
                ->orderBy('sales.entryDate', 'desc')
                ->get();

            // Group rows by invoiceNo
            $sales = $rows->groupBy('invoiceNo')->map(function ($items) {
                $first = $items->first();
                return [
                    'sale_id'     => $first->sale_id,
                    'invoiceNo'   => $first->invoiceNo,
                    'entryDate'   => $first->entryDate,
                    'discount'    => $first->discount,
                    'paymentMode' => $first->paymentMode,
                    'table_name'  => $first->table_name,
                    'table_number' => $first->table_number,
                    'status'      => $first->sale_status,
                    'total_sd'    => $items->sum('sd'),
                    'total_vat'   => $items->sum('vat'),
                    'total'       => $items->sum('item_total'),
                    'products'    => $items->map(function ($item) {
                        return [
                            'product_name' => $item->product_name,
                            'quantity'     => $item->quantity,
                            'price'        => $item->price,
                            'sd'           => $item->sd,
                            'vat'          => $item->vat,
                            'total'        => $item->item_total,
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $sales,
            ]);
        } catch (\Exception $e) {
            Log::error('Sale index error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch sales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize a new sale for a table
     */
    public function initialize(Request $request)
    {
        try {
            $data = $request->validate([
                'table_id' => 'required|exists:tables,id',
                'status' => 'required|in:active,printed,completed'
            ]);

            // Check if table already has an active sale
            $existingSale = Sale::where('table_id', $data['table_id'])
                ->where('status', 'active')
                ->where('validity', 1)
                ->first();

            if ($existingSale) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This table already has an active sale',
                    'sale_id' => $existingSale->id
                ], 400);
            }

            $sale = Sale::create([
                'entryDate' => now()->format('Y-m-d'),
                'table_id' => $data['table_id'],
                'status' => $data['status'],
                'validity' => 1,
                'total' => 0,
                'discount' => 0,
                'sd' => 0,
                'vat' => 0,
                'received' => 0,
                'change' => 0,
                'paymentMode' => 'Cash', // ✅ Added paymentMode
            ]);

            return response()->json([
                'status' => 'success',
                'sale_id' => $sale->id,
                'message' => 'Sale initialized successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Sale initialization error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-save sale details
     */
    public function autoSave(Request $request, $id)
    {
        try {
            $sale = Sale::find($id);
            if (!$sale) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale not found'
                ], 404);
            }

            // Delete existing details
            Saledetails::where('sale_id', $sale->id)->delete();

            // Create new details
            foreach ($request->products as $product) {
                Saledetails::create([
                    'sale_id' => $sale->id,
                    'invoiceNo' => $sale->invoiceNo,
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $product['quantity'],
                    'price' => $product['price'],
                    'total' => $product['price'] * $product['quantity'],
                    'vat' => $product['vat'] ?? 0,
                    'sd' => $product['sd'] ?? 0,
                    'validity' => 1,
                ]);
            }

            $sale->update([
                'total' => $request->total,
                'status' => $request->status ?? 'active'
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Sale auto-saved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Auto-save error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to auto-save sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invoice number
     */
    private function generateInvoiceNo()
    {
        $lastSale = Sale::orderBy('id', 'desc')->first();
        $lastNumber = 0;

        if ($lastSale && $lastSale->invoiceNo) {
            preg_match('/(\d+)$/', $lastSale->invoiceNo, $matches);
            if (isset($matches[1])) {
                $lastNumber = (int)$matches[1];
            }
        }

        $nextNumber = $lastNumber + 1;
        return 'INV-' . date('Y') . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'total' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'vat' => 'nullable|numeric',
            'sd' => 'nullable|numeric',
            'paymentMode' => 'required|string',
            'received' => 'required|numeric',
            'change' => 'required|numeric',
            'table_id' => 'required|exists:tables,id',
            'status' => 'required|in:active,printed,completed',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric',
            'products.*.quantity' => 'required|numeric|min:1',
            'products.*.vat' => 'nullable|numeric',
            'products.*.sd' => 'nullable|numeric',
        ]);

        DB::beginTransaction();
        try {
            $invoiceNo = $this->generateInvoiceNo();
            
            $sale = Sale::create([
                'entryDate'   => $request->entryDate ?? now()->format('Y-m-d'),
                'invoiceNo'   => $invoiceNo,
                'discount'    => $data['discount'] ?? 0,
                'sd'          => $data['sd'] ?? 0,
                'vat'         => $data['vat'] ?? 0,
                'total'       => $data['total'],
                'received'    => $data['received'],
                'change'      => $data['change'],
                'paymentMode' => $data['paymentMode'],
                'table_id'    => $data['table_id'],
                'status'      => $data['status'],
                'user'        => auth()->id() ?? null,
                'validity'    => 1,
            ]);

            // Save sale details
            foreach ($data['products'] as $p) {
                Saledetails::create([
                    'sale_id'      => $sale->id,
                    'invoiceNo'    => $sale->invoiceNo,
                    'product_id'   => $p['id'],
                    'product_name' => $p['name'],
                    'quantity'     => $p['quantity'],
                    'price'        => $p['price'],
                    'sd'           => $p['sd'] ?? 0,
                    'vat'          => $p['vat'] ?? 0,
                    'total'        => $p['price'] * $p['quantity'],
                    'category_id'  => $p['category'] ?? null,
                    'user'         => auth()->id() ?? null,
                    'validity'     => 1,
                ]);

                // Update stock
                $product = BranchStore::where('product_id', $p['id'])->first();
                if ($product) {
                    $currentStock = (float) $product->stock;
                    $quantitySold = (float) $p['quantity'];
                    $newStock = $currentStock - $quantitySold;

                    if ($newStock < 0) {
                        throw new \Exception("Not enough stock for product {$product->product_name}");
                    }

                    $product->prv_stock   = $currentStock;
                    $product->stock       = $newStock;
                    $product->after_stock = $newStock;
                    $product->save();
                }
            }

            // Update table status to available
            $table = Table::find($data['table_id']);
            if ($table) {
                $table->status = 'available';
                $table->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sale recorded successfully',
                'sale_id' => $sale->id,
                'invoiceNo' => $sale->invoiceNo,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Sale store error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show($id)
    {
        try {
            $sale = Sale::with(['details', 'table'])->find($id);
            
            if (!$sale) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $sale
            ]);
        } catch (\Exception $e) {
            Log::error('Sale show error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified sale.
     */
    public function update(Request $request, $id)
    {
        try {
            $sale = Sale::find($id);
            if (!$sale) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale not found'
                ], 404);
            }

            $data = $request->validate([
                'discount' => 'nullable|numeric',
                'vat' => 'nullable|numeric',
                'sd' => 'nullable|numeric',
                'total' => 'nullable|numeric',
                'received' => 'nullable|numeric',
                'change' => 'nullable|numeric',
                'paymentMode' => 'nullable|string',
                'status' => 'nullable|in:active,printed,completed',
            ]);

            $sale->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Sale updated successfully',
                'data' => $sale
            ]);
        } catch (\Exception $e) {
            Log::error('Sale update error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified sale from storage.
     */
    public function destroy($id)
    {
        try {
            $data = Sale::find($id);
            if (!$data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale not found'
                ], 404);
            }

            $data->validity = 0;
            $data->save();

            $dataDetails = Saledetails::where('sale_id', $id)->get();
            foreach ($dataDetails as $detail) {
                $detail->validity = 0;
                $detail->save();
            }

            // Update table status if table exists
            if ($data->table_id) {
                $table = Table::find($data->table_id);
                if ($table) {
                    $table->status = 'available';
                    $table->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Sale deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Sale delete error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active sale for a specific table
     */
    public function getActiveSaleByTable($tableId)
    {
        try {
            $sale = Sale::where('table_id', $tableId)
                ->where('status', 'active')
                ->where('validity', 1)
                ->with('details')
                ->first();

            if (!$sale) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active sale found for this table'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $sale
            ]);
        } catch (\Exception $e) {
            Log::error('Get active sale error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch active sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sale status (active, printed, completed)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $sale = Sale::find($id);
            if (!$sale) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sale not found'
                ], 404);
            }

            $request->validate([
                'status' => 'required|in:active,printed,completed'
            ]);

            $sale->status = $request->status;
            $sale->save();

            // If completed, update table status
            if ($request->status === 'completed' && $sale->table_id) {
                $table = Table::find($sale->table_id);
                if ($table) {
                    $table->status = 'available';
                    $table->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Sale status updated successfully',
                'data' => $sale
            ]);
        } catch (\Exception $e) {
            Log::error('Update status error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update sale status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's sales summary
     */
    public function todaySummary()
    {
        try {
            $today = now()->format('Y-m-d');
            
            $summary = Sale::where('entryDate', $today)
                ->where('validity', 1)
                ->where('status', 'completed')
                ->select(
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(total) as total_revenue'),
                    DB::raw('SUM(discount) as total_discount'),
                    DB::raw('SUM(vat) as total_vat'),
                    DB::raw('SUM(sd) as total_sd')
                )
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Today summary error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}