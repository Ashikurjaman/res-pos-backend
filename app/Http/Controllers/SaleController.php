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
    public function index(Request $request)
    {
        $formDate = $request->formDate;
        $toDate = $request->toDate;
        $invoiceNo = $request->invoiceNo;

        $formDateFormat = $formDate ? date('Y-m-d', strtotime($formDate)) : null;
        $toDateFormat = $toDate ? date('Y-m-d', strtotime($toDate)) : null;

        $query = DB::table('sales')
            ->leftJoin('saledetails', 'saledetails.sale_id', '=', 'sales.id')
            ->select(
                'sales.id as sale_id',
                'sales.entryDate',
                'sales.invoiceNo',
                'sales.discount',
                'sales.paymentMode',
                'sales.validity',
                'saledetails.product_name',
                'saledetails.quantity',
                'saledetails.price',
                'saledetails.sd',
                'saledetails.vat',
                'saledetails.total'
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

        // 🔑 Group rows by invoiceNo
        $sales = $rows->groupBy('invoiceNo')->map(function ($items) {
            $first = $items->first();
            return [
                'sale_id'     => $first->sale_id,
                'invoiceNo'   => $first->invoiceNo,
                'entryDate'   => $first->entryDate,
                'discount'    => $first->discount,
                'paymentMode' => $first->paymentMode,
                'total_sd'    => $items->sum('sd'),
                'total_vat'   => $items->sum('vat'),
                'total'       => $items->sum('total'),
                'products'    => $items->map(function ($item) {
                    return [
                        'product_name' => $item->product_name,
                        'quantity'     => $item->quantity,
                        'price'        => $item->price,
                        'sd'           => $item->sd,
                        'vat'          => $item->vat,
                        'total'        => $item->total,
                    ];
                })->values(),
                // 👇 optional: calculate invoice total
                'total' => $items->sum('total'),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $sales,
        ]);
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
                'entryDate'   => $request->entryDate,
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
        // dd($id);
        $data = Sale::find($id);
        if (!$data) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        $data->validity = 0;
        $data->save();

        $dataDetails = Saledetails::where('sale_id', $id)->get();
        if ($dataDetails->isEmpty()) {
            return response()->json(['message' => 'Sale details not found'], 404);
        }

        foreach ($dataDetails as $detail) {
            $detail->validity = 0;
            $detail->save();
        }
        return response()->json(['message' => 'Sale deleted successfully']);
    }
}
