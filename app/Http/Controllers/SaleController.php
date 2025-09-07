<?php

namespace App\Http\Controllers;

use App\Models\BranchStore;
use App\Models\Sale;
use App\Models\Saledetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
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
        //
    }

    private function generateInvoiceNo()
    {
        $lastSale = Sale::orderBy('id', 'desc')->first(); // order by PK, safer
        $lastNumber = 0;

        if ($lastSale && $lastSale->invoiceNo) {
            // Extract last 5 digits
            preg_match('/(\d+)$/', $lastSale->invoiceNo, $matches);
            if (isset($matches[1])) {
                $lastNumber = (int)$matches[1];
            }
        }

        $nextNumber = $lastNumber + 1;

        // Example: INV-2025-00001
        return 'INV-' . date('Y') . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created resource in storage.
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
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric',
            'products.*.quantity' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            //code...

            // dd($request->all());
            $invoiceNo = $this->generateInvoiceNo();
            $sale = Sale::create([
                'entryDate'   => now()->toDateString(),
                'invoiceNo'   => $invoiceNo, // you can make your own auto generator
                'discount'    => $data['discount'] ?? 0,
                'sd'          => $data['sd'] ?? 0,
                'vat'         => $data['vat'] ?? 0,
                'total'       => $data['total'],
                'received'    => $data['received'],
                'change'      => $data['change'],
                'paymentMode' => $data['paymentMode'],
                'user'        => auth()->id() ?? null,
                'validity'    => 1,
            ]);

            foreach ($data['products'] as $p) {
                Saledetails::create([
                    'sale_id'      => $sale->id,
                    'invoiceNo'    => $sale->invoiceNo,
                    'product_id'   => $p['id'],
                    'product_name' => $p['name'],
                    'quantity'     => $p['quantity'],
                    'price'        => $p['price'],
                    'sd'           => $p['sd'] ?? 0, // per item if needed
                    'vat'          => $p['vat'] ?? 0, // per item if needed
                    'total'        => $p['price'] * $p['quantity'],
                    'category_id'  => 1, // replace with real category lookup if needed
                    'user'         => auth()->id() ?? null,
                    'validity'     => 1,
                ]);

                $product = BranchStore::where('product_id', $p['id'])->first();
                if ($product) {
                    $currentStock = (float) $product->stock;       // cast to float
                    $quantitySold = (float) $p['quantity'];       // cast to float
                    // dd($currentStock);
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
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale recorded successfully',
                'sale_id' => $sale->id,
                'invoiceNo' => $sale->invoiceNo,
            ], 201);
        } catch (\Throwable $e) {
            //throw $th;
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save sale',
                'error' => $e->getMessage(),
            ], 500);
        }
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
