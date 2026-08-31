<?php

namespace App\Http\Controllers;

use App\Models\OutletDespatch;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OutletDespatchController extends Controller
{
    protected $stockTransferService;

    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    /**
     * Display a listing of despatches.
     */
    public function index(Request $request)
    {
        $query = OutletDespatch::with(['sourceOutlet', 'destOutlet', 'despatchedBy', 'request'])
            ->active();

        if ($request->outlet_id) {
            $query->where(function ($q) use ($request) {
                $q->where('source_outlet_id', $request->outlet_id)
                    ->orWhere('dest_outlet_id', $request->outlet_id);
            });
        }

        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        $despatches = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $despatches,
        ]);
    }

    /**
     * Create despatch from request.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_id' => 'required|exists:outlet_requests,id',
                'despatch_date' => 'nullable|date',
                'source_outlet_id' => 'nullable|exists:outlets,id',
                'vehicle_no' => 'nullable|string|max:50',
                'driver_name' => 'nullable|string|max:100',
                'items' => 'required|array|min:1',
                'items.*.request_detail_id' => 'required|exists:outlet_request_details,id',
                'items.*.despatch_qty' => 'required|numeric|min:0.001',
                'items.*.remarks' => 'nullable|string',
                'remarks' => 'nullable|string',
            ]);

            $despatch = $this->stockTransferService->createDespatchFromRequest(
                $validated['request_id'],
                $validated
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Despatch created successfully',
                'data' => $despatch,
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
                'message' => 'Failed to create despatch',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified despatch.
     */
    public function show($id)
    {
        $despatch = OutletDespatch::with([
            'sourceOutlet',
            'destOutlet',
            'despatchedBy',
            'request',
            'details',
            'details.product',
            'details.unit',
            'details.requestDetail',
            'receives',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $despatch,
        ]);
    }
}
