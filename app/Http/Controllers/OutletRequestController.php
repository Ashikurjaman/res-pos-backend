<?php
namespace App\Http\Controllers;

use App\Models\OutletRequest;
use App\Models\OutletRequestDetail;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        $query = OutletRequest::with(['requestingOutlet', 'sourceOutlet', 'requestedBy', 'approvedBy'])
            ->active();

        // Filter by outlet
        if ($request->outlet_id) {
            $query->where('requesting_outlet_id', $request->outlet_id);
        }

        // Filter by status
        if ($request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->type) {
            $query->where('request_type', $request->type);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
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

            return response()->json([
                'status' => 'success',
                'message' => 'Request created successfully',
                'data' => $outletRequest
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
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
        $outletRequest = OutletRequest::with([
            'requestingOutlet',
            'sourceOutlet',
            'requestedBy',
            'approvedBy',
            'details',
            'details.product',
            'details.unit',
            'despatches',
            'despatches.details'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $outletRequest
        ]);
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

            $outletRequest = $this->stockTransferService->approveRequest(
                $id,
                $validated['items'],
                $validated['remarks'] ?? null
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Request approved successfully',
                'data' => $outletRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending requests count.
     */
    public function pendingCount()
    {
        $count = OutletRequest::where('status', OutletRequest::STATUS_PENDING)
            ->active()
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => ['pending_count' => $count]
        ]);
    }
}
