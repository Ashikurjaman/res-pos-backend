<?php

namespace App\Http\Controllers;

use App\Models\OutletDespatch;
use App\Models\OutletDespatchDetail;
use App\Models\OutletRequest;
use App\Models\OutletRequestDetail;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        try {
            $query = OutletDespatch::with([
                'sourceOutlet',
                'destOutlet',
                'despatchedBy',
                'request',
                'details',
                'details.product',
                'details.unit'
            ])->active();

            // Filter by outlet (source or destination)
            if ($request->outlet_id) {
                $query->where(function ($q) use ($request) {
                    $q->where('source_outlet_id', $request->outlet_id)
                        ->orWhere('dest_outlet_id', $request->outlet_id);
                });
            }

            // Filter by status
            if ($request->status !== null) {
                $query->where('status', $request->status);
            }

            // Filter by request_id
            if ($request->request_id) {
                $query->where('request_id', $request->request_id);
            }

            // Search by despatch number or vehicle
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('despatch_no', 'LIKE', "%{$search}%")
                        ->orWhere('vehicle_no', 'LIKE', "%{$search}%")
                        ->orWhere('driver_name', 'LIKE', "%{$search}%");
                });
            }

            // Sort
            $sortBy = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $despatches = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $despatches,
                'message' => 'Despatches fetched successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch despatches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch despatches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create despatch from request.
     */
    public function store(Request $request)
    {
        try {
            // ✅ Validate request
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

            Log::info('Creating despatch', [
                'request_id' => $validated['request_id'],
                'items_count' => count($validated['items']),
                'user_id' => auth()->id()
            ]);

            DB::beginTransaction();

            // ✅ Get the request
            $outletRequest = OutletRequest::with(['details'])->findOrFail($validated['request_id']);

            // ✅ Generate despatch number
            $despatchNo = $this->generateDespatchNumber();

            // ✅ Create despatch
            $despatch = OutletDespatch::create([
                'despatch_no' => $despatchNo,
                'request_id' => $validated['request_id'],
                'despatch_date' => $validated['despatch_date'] ?? now(),
                'source_outlet_id' => $validated['source_outlet_id'] ?? $outletRequest->requesting_outlet_id,
                'dest_outlet_id' => $outletRequest->requesting_outlet_id,
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'status' => 0, // Pending
                'despatched_by' => auth()->id(),
                'remarks' => $validated['remarks'] ?? null,
                'validity' => 1,
            ]);

            // ✅ Create despatch details
            foreach ($validated['items'] as $item) {
                $requestDetail = OutletRequestDetail::find($item['request_detail_id']);

                // Check if despatch quantity exceeds remaining
                $remaining = $requestDetail->approved_qty - $requestDetail->despatched_qty;
                if ($item['despatch_qty'] > $remaining) {
                    throw new \Exception("Despatch quantity exceeds remaining quantity for product ID: {$requestDetail->product_id}");
                }

                OutletDespatchDetail::create([
                    'despatch_id' => $despatch->id,
                    'request_detail_id' => $item['request_detail_id'],
                    'product_id' => $requestDetail->product_id,
                    'unit_id' => $requestDetail->unit_id,
                    'despatch_qty' => $item['despatch_qty'],
                    'remarks' => $item['remarks'] ?? null,
                ]);

                // ✅ Update request detail despatched quantity
                $requestDetail->despatched_qty += $item['despatch_qty'];
                $requestDetail->save();
            }

            // ✅ Update request status
            $this->updateRequestStatus($outletRequest);

            DB::commit();

            // ✅ Load relations for response
            $despatch->load([
                'sourceOutlet',
                'destOutlet',
                'despatchedBy',
                'request',
                'details',
                'details.product',
                'details.unit'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Despatch created successfully',
                'data' => $despatch
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Despatch validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create despatch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create despatch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified despatch.
     */
    public function show($id)
    {
        try {
            $despatch = OutletDespatch::with([
                'sourceOutlet',
                'destOutlet',
                'despatchedBy',
                'request',
                'request.requestingOutlet',
                'request.sourceOutlet',
                'details',
                'details.product',
                'details.product.category',
                'details.unit',
                'details.requestDetail',
                'receives',
                'receives.details'
            ])->findOrFail($id);

            // ✅ Calculate totals
            $despatch->total_qty = $despatch->details->sum('despatch_qty');
            $despatch->received_qty = $despatch->receives->sum(function ($receive) {
                return $receive->details->sum('received_qty');
            });

            Log::info('Despatch fetched', ['despatch_id' => $id]);

            return response()->json([
                'success' => true,
                'data' => $despatch,
                'message' => 'Despatch fetched successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch despatch', [
                'despatch_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Despatch not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update despatch status.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:0,1,2,3', // 0=Pending, 1=In Transit, 2=Delivered, 3=Cancelled
            ]);

            $despatch = OutletDespatch::findOrFail($id);

            // ✅ Check if status change is valid
            if ($despatch->status == 3 && $validated['status'] != 3) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change status of cancelled despatch'
                ], 400);
            }

            $despatch->status = $validated['status'];
            $despatch->save();

            // ✅ If status is Delivered (2), update related request status
            if ($validated['status'] == 2) {
                $this->updateRequestStatusAfterDelivery($despatch);
            }

            return response()->json([
                'success' => true,
                'message' => 'Despatch status updated successfully',
                'data' => $despatch
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update despatch status', [
                'despatch_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update despatch status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel despatch.
     */
    public function cancel($id)
    {
        try {
            $despatch = OutletDespatch::findOrFail($id);

            // Only allow cancellation if status is Pending (0) or In Transit (1)
            if (!in_array($despatch->status, [0, 1])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel despatch with current status'
                ], 400);
            }

            DB::beginTransaction();

            $despatch->status = 3; // Cancelled
            $despatch->save();

            // ✅ Restore despatch quantities back to request details
            foreach ($despatch->details as $detail) {
                $requestDetail = OutletRequestDetail::find($detail->request_detail_id);
                if ($requestDetail) {
                    $requestDetail->despatched_qty -= $detail->despatch_qty;
                    $requestDetail->save();
                }
            }

            // ✅ Update request status
            $outletRequest = OutletRequest::find($despatch->request_id);
            if ($outletRequest) {
                $this->updateRequestStatus($outletRequest);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Despatch cancelled successfully',
                'data' => $despatch
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel despatch', [
                'despatch_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel despatch',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get despatch statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $query = OutletDespatch::active();

            if ($request->outlet_id) {
                $query->where(function ($q) use ($request) {
                    $q->where('source_outlet_id', $request->outlet_id)
                        ->orWhere('dest_outlet_id', $request->outlet_id);
                });
            }

            $total = $query->count();
            $pending = (clone $query)->where('status', 0)->count();
            $inTransit = (clone $query)->where('status', 1)->count();
            $delivered = (clone $query)->where('status', 2)->count();
            $cancelled = (clone $query)->where('status', 3)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'pending' => $pending,
                    'in_transit' => $inTransit,
                    'delivered' => $delivered,
                    'cancelled' => $cancelled,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get despatch statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get despatch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate despatch number.
     */
    private function generateDespatchNumber()
    {
        $date = now()->format('Ymd');
        $lastDespatch = OutletDespatch::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastDespatch ? intval(substr($lastDespatch->despatch_no, -4)) + 1 : 1;
        $sequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);

        return "DSP{$date}{$sequence}";
    }

    /**
     * Update request status based on despatch status.
     */
    private function updateRequestStatus($outletRequest)
    {
        // Refresh request details
        $outletRequest->load(['details']);

        $allDespatched = $outletRequest->details->every(function ($detail) {
            return $detail->approved_qty <= $detail->despatched_qty;
        });

        $anyDespatched = $outletRequest->details->some(function ($detail) {
            return $detail->despatched_qty > 0;
        });

        if ($allDespatched) {
            $outletRequest->status = OutletRequest::STATUS_DESPATCHED;
        } elseif ($anyDespatched) {
            $outletRequest->status = OutletRequest::STATUS_PARTIAL_APPROVED;
        } else {
            $outletRequest->status = OutletRequest::STATUS_APPROVED;
        }

        $outletRequest->save();
    }

    /**
     * Update request status after delivery.
     */
    private function updateRequestStatusAfterDelivery($despatch)
    {
        $outletRequest = OutletRequest::find($despatch->request_id);
        if ($outletRequest) {
            // Check if all items are despatched and delivered
            $allDespatched = $outletRequest->details->every(function ($detail) {
                return $detail->approved_qty <= $detail->despatched_qty;
            });

            if ($allDespatched) {
                $outletRequest->status = OutletRequest::STATUS_RECEIVED;
                $outletRequest->save();
            }
        }
    }
}
