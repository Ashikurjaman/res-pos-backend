<?php

namespace App\Http\Controllers;

use App\Models\OutletReceive;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OutletReceiveController extends Controller
{
    protected $stockTransferService;

    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    /**
     * Display a listing of receives.
     */
    public function index(Request $request)
    {
        $query = OutletReceive::with(['receivingOutlet', 'receivedBy', 'despatch'])
            ->active();

        if ($request->outlet_id) {
            $query->where('receiving_outlet_id', $request->outlet_id);
        }

        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        $receives = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $receives,
        ]);
    }

    /**
     * Receive stock.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'despatch_id' => 'required|exists:outlet_despatches,id',
                'receive_date' => 'nullable|date',
                'items' => 'required|array|min:1',
                'items.*.despatch_detail_id' => 'required|exists:outlet_despatch_details,id',
                'items.*.received_qty' => 'required|numeric|min:0',
                'items.*.short_qty' => 'nullable|numeric|min:0',
                'items.*.damage_qty' => 'nullable|numeric|min:0',
                'items.*.remarks' => 'nullable|string',
                'remarks' => 'nullable|string',
            ]);

            $receive = $this->stockTransferService->receiveStock(
                $validated['despatch_id'],
                $validated
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Stock received successfully',
                'data' => $receive,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to receive stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified receive.
     */
    public function show($id)
    {
        $receive = OutletReceive::with([
            'receivingOutlet',
            'receivedBy',
            'despatch',
            'details',
            'details.product',
            'details.despatchDetail',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $receive,
        ]);
    }
}
