<?php
namespace App\Http\Controllers;

use App\Models\OutletRequest;
use App\Models\OutletRequestDetail;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OutletRequestController extends Controller
{
    protected $stockTransferService;

    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    /**
     * Display a listing of requests.
     */
    public function index(Request $request)
    {
        try {
            $query = OutletRequest::with([
                'requestingOutlet',
                'sourceOutlet',
                'requestedBy',
                'approvedBy'
            ])->active();

            if ($request->outlet_id) {
                $query->where('requesting_outlet_id', $request->outlet_id);
            }

            if ($request->status !== null) {
                $query->where('status', $request->status);
            }

            if ($request->type) {
                $query->where('request_type', $request->type);
            }

            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('request_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('requestingOutlet', function ($q2) use ($search) {
                            $q2->where('outlet_name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $requests = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch requests', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created request.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'request_date' => 'nullable|date',
                'requesting_outlet_id' => 'required|exists:outlets,id',
                'source_outlet_id' => 'nullable|exists:outlets,id',
                'request_type' => 'required|in:1,2',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:product,id',
                'items.*.unit_id' => 'required|exists:unitls,id',
                'items.*.requested_qty' => 'required|numeric|min:0.001',
                'items.*.remarks' => 'nullable|string',
                'remarks' => 'nullable|string'
            ]);

            $requestData = $validated;
            $requestData['requested_by'] = Auth::id();

            $outletRequest = $this->stockTransferService->createRequest($requestData);
            $outletRequest->load(['requestingOutlet', 'sourceOutlet', 'requestedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Request created successfully',
                'data' => $outletRequest
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create request', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified request.
     */
    public function show($id)
    {
        try {
            $outletRequest = OutletRequest::with([
                'requestingOutlet',
                'sourceOutlet',
                'requestedBy',
                'approvedBy',
                'details',
                'details.product',
                'details.product.category',
                'details.unit',
                'despatches',
                'despatches.details'
            ])->findOrFail($id);

            $outletRequest->total_approved = $outletRequest->details->sum('approved_qty');
            $outletRequest->total_despatched = $outletRequest->details->sum('despatched_qty');
            $outletRequest->total_remaining = $outletRequest->total_approved - $outletRequest->total_despatched;

            return response()->json([
                'success' => true,
                'data' => $outletRequest
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Request not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * ✅ Get requests ready for despatch
     * Shows ONLY APPROVED (1) or PARTIAL_APPROVED (2) requests
     * that have remaining quantity (approved_qty > despatched_qty)
     */
    public function pendingForDespatch(Request $request)
    {
        try {
            $outletId = $request->outlet_id;
            $search = $request->search;

            // ✅ শুধুমাত্র Approved (1) এবং Partial Approved (2) দেখাবে
            $query = OutletRequest::with([
                'requestingOutlet',
                'sourceOutlet',
                'details',
                'details.product',
                'details.product.category',
                'details.unit'
            ])
            ->whereIn('status', [
                OutletRequest::STATUS_APPROVED,        // 1
                OutletRequest::STATUS_PARTIAL_APPROVED // 2
            ])
            ->whereHas('details', function ($q) {
                // ✅ যেগুলোর remaining quantity আছে (approved_qty > despatched_qty)
                $q->whereRaw('approved_qty > despatched_qty');
            })
            ->active();

            if ($outletId) {
                $query->where('requesting_outlet_id', $outletId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('request_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('requestingOutlet', function ($q2) use ($search) {
                            $q2->where('outlet_name', 'LIKE', "%{$search}%");
                        });
                });
            }

            $requests = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    // ✅ Calculate totals
                    $request->total_approved = $request->details->sum('approved_qty');
                    $request->total_despatched = $request->details->sum('despatched_qty');
                    $request->total_remaining = $request->total_approved - $request->total_despatched;

                    // ✅ Filter only items with remaining quantity
                    $request->details = $request->details->filter(function ($detail) {
                        return $detail->approved_qty > $detail->despatched_qty;
                    })->map(function ($detail) {
                        $detail->category_name = $detail->product?->category?->category_name ?? 'General';
                        $detail->product_code = $detail->product?->product_code ?? '';
                        $detail->current_stock = 0;
                        return $detail;
                    })->values();

                    return $request;
                })
                ->filter(function ($request) {
                    return $request->details->count() > 0;
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $requests,
                'message' => 'Requests ready for despatch fetched successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch requests for despatch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a request.
     */
    public function approve(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.detail_id' => 'required|exists:outlet_request_details,id',
                'items.*.approved_qty' => 'required|numeric|min:0',
                'items.*.remarks' => 'nullable|string',
                'remarks' => 'nullable|string'
            ]);

            DB::beginTransaction();

            $outletRequest = OutletRequest::findOrFail($id);

            foreach ($validated['items'] as $item) {
                $detail = OutletRequestDetail::find($item['detail_id']);
                if ($detail) {
                    $detail->approved_qty = $item['approved_qty'];
                    $detail->remarks = $item['remarks'] ?? $detail->remarks;
                    $detail->save();
                }
            }

            // Update request status
            $allApproved = $outletRequest->details->every(function ($detail) {
                return $detail->approved_qty >= $detail->requested_qty;
            });

            $anyApproved = $outletRequest->details->some(function ($detail) {
                return $detail->approved_qty > 0;
            });

            if ($allApproved) {
                $outletRequest->status = OutletRequest::STATUS_APPROVED;
            } elseif ($anyApproved) {
                $outletRequest->status = OutletRequest::STATUS_PARTIAL_APPROVED;
            } else {
                $outletRequest->status = OutletRequest::STATUS_REJECTED;
            }

            $outletRequest->approved_by = Auth::id();
            $outletRequest->approved_at = now();
            $outletRequest->remarks = $validated['remarks'] ?? $outletRequest->remarks;
            $outletRequest->save();

            DB::commit();

            $outletRequest->load([
                'requestingOutlet',
                'sourceOutlet',
                'requestedBy',
                'approvedBy',
                'details',
                'details.product',
                'details.unit'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request approved successfully',
                'data' => $outletRequest
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending approval count.
     */
    public function pendingCount()
    {
        try {
            $count = OutletRequest::where('status', OutletRequest::STATUS_PENDING)
                ->active()
                ->count();

            return response()->json([
                'success' => true,
                'data' => ['pending_count' => $count]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending count', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get pending count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update request status.
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:' . implode(',', [
                    OutletRequest::STATUS_PENDING,
                    OutletRequest::STATUS_APPROVED,
                    OutletRequest::STATUS_PARTIAL_APPROVED,
                    OutletRequest::STATUS_REJECTED,
                    OutletRequest::STATUS_DESPATCHED,
                    OutletRequest::STATUS_RECEIVED,
                    OutletRequest::STATUS_CLOSED
                ])
            ]);

            $outletRequest = OutletRequest::findOrFail($id);
            $outletRequest->status = $validated['status'];
            $outletRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Request status updated successfully',
                'data' => $outletRequest
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update request status', [
                'request_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update request status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete request.
     */
    public function destroy($id)
    {
        try {
            $outletRequest = OutletRequest::findOrFail($id);

            if (!in_array($outletRequest->status, [0, 3])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete request that is already processed'
                ], 400);
            }

            $outletRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete request', [
                'request_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete request',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
