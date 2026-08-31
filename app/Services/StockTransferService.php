<?php
// app/Services/StockTransferService.php

namespace App\Services;

use App\Models\OutletRequest;
use App\Models\OutletRequestDetail;
use App\Models\OutletDespatch;
use App\Models\OutletDespatchDetail;
use App\Models\OutletReceive;
use App\Models\OutletReceiveDetail;
use App\Models\OutletStockLedger;
use App\Models\BranchStore;
use App\Models\HeadOfficeStock;
use App\Models\Product;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StockTransferService
{
    /**
     * Generate request number
     */
    public function generateRequestNo()
    {
        $prefix = 'REQ';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $count = OutletRequest::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate despatch number
     */
    public function generateDespatchNo()
    {
        $prefix = 'DESP';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $count = OutletDespatch::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate receive number
     */
    public function generateReceiveNo()
    {
        $prefix = 'RECV';
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $count = OutletReceive::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a stock request
     */
    public function createRequest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $request = OutletRequest::create([
                'request_no' => $this->generateRequestNo(),
                'request_date' => $data['request_date'] ?? Carbon::now()->format('Y-m-d'),
                'requesting_outlet_id' => $data['requesting_outlet_id'],
                'source_outlet_id' => $data['source_outlet_id'] ?? 1, // Default to HO
                'request_type' => $data['request_type'] ?? OutletRequest::TYPE_HO_REQUEST,
                'status' => OutletRequest::STATUS_PENDING,
                'requested_by' => Auth::id(),
                'remarks' => $data['remarks'] ?? null,
                'validity' => 1,
            ]);

            foreach ($data['items'] as $item) {
                OutletRequestDetail::create([
                    'request_id' => $request->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'requested_qty' => $item['requested_qty'],
                    'approved_qty' => 0,
                    'despatched_qty' => 0,
                    'received_qty' => 0,
                    'remarks' => $item['remarks'] ?? null,
                    'validity' => 1,
                ]);
            }

            return $request->load('details', 'requestingOutlet');
        });
    }

    /**
     * Approve a request (full or partial)
     */
    public function approveRequest($requestId, array $approvedItems, $remarks = null)
    {
        return DB::transaction(function () use ($requestId, $approvedItems, $remarks) {
            $request = OutletRequest::findOrFail($requestId);

            if ($request->status != OutletRequest::STATUS_PENDING) {
                throw new \Exception('Request is not pending');
            }

            $totalApproved = 0;
            $totalRequested = 0;

            foreach ($approvedItems as $item) {
                $detail = OutletRequestDetail::findOrFail($item['detail_id']);
                if ($detail->request_id != $requestId) {
                    throw new \Exception('Invalid request detail');
                }

                $approvedQty = $item['approved_qty'] ?? 0;
                $detail->update([
                    'approved_qty' => $approvedQty,
                    'remarks' => $item['remarks'] ?? $detail->remarks,
                ]);

                $totalApproved += $approvedQty;
                $totalRequested += $detail->requested_qty;
            }

            if ($totalApproved == 0) {
                $status = OutletRequest::STATUS_REJECTED;
            } elseif ($totalApproved >= $totalRequested) {
                $status = OutletRequest::STATUS_APPROVED;
            } else {
                $status = OutletRequest::STATUS_PARTIAL_APPROVED;
            }

            $request->update([
                'status' => $status,
                'approved_by' => Auth::id(),
                'approved_at' => Carbon::now(),
                'remarks' => $remarks ?? $request->remarks,
            ]);

            return $request->load('details');
        });
    }

    /**
     * Create despatch from approved request
     */
    public function createDespatchFromRequest($requestId, array $data)
    {
        return DB::transaction(function () use ($requestId, $data) {
            $request = OutletRequest::findOrFail($requestId);

            if (!in_array($request->status, [
                OutletRequest::STATUS_APPROVED,
                OutletRequest::STATUS_PARTIAL_APPROVED
            ])) {
                throw new \Exception('Request is not approved');
            }

            // Determine source outlet
            $sourceOutletId = $data['source_outlet_id'] ?? $request->source_outlet_id;
            $sourceType = $sourceOutletId == 1 ? OutletDespatch::SOURCE_HEAD_OFFICE : OutletDespatch::SOURCE_OUTLET;

            // Create despatch
            $despatch = OutletDespatch::create([
                'despatch_no' => $this->generateDespatchNo(),
                'despatch_date' => $data['despatch_date'] ?? Carbon::now()->format('Y-m-d'),
                'request_id' => $requestId,
                'source_type' => $sourceType,
                'source_outlet_id' => $sourceOutletId,
                'dest_outlet_id' => $request->requesting_outlet_id,
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'despatched_by' => Auth::id(),
                'status' => OutletDespatch::STATUS_PENDING,
                'remarks' => $data['remarks'] ?? null,
                'validity' => 1,
            ]);

            $totalQty = 0;
            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $detail = OutletRequestDetail::findOrFail($item['request_detail_id']);
                if ($detail->request_id != $requestId) {
                    throw new \Exception('Invalid request detail');
                }

                $despatchQty = $item['despatch_qty'];
                $remaining = $detail->approved_qty - $detail->despatched_qty;

                if ($despatchQty > $remaining) {
                    throw new \Exception("Despatch quantity exceeds remaining approved quantity for product");
                }

                // Get purchase price
                $product = Product::findOrFail($detail->product_id);
                $purchasePrice = $product->pur_price ?? 0;

                // Create despatch detail
                $despatchDetail = OutletDespatchDetail::create([
                    'despatch_id' => $despatch->id,
                    'request_detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'unit_id' => $detail->unit_id,
                    'despatch_qty' => $despatchQty,
                    'purchase_price' => $purchasePrice,
                    'total_amount' => $purchasePrice * $despatchQty,
                    'remarks' => $item['remarks'] ?? null,
                    'validity' => 1,
                ]);

                // Update request detail despatch qty
                $detail->increment('despatched_qty', $despatchQty);

                $totalQty += $despatchQty;
                $totalAmount += $purchasePrice * $despatchQty;
            }

            // Update despatch totals
            $despatch->update([
                'total_qty' => $totalQty,
                'total_amount' => $totalAmount,
            ]);

            // Update request status
            $this->updateRequestStatus($requestId);

            // Deduct from source stock
            $this->deductSourceStock($despatch);

            return $despatch->load('details', 'details.product');
        });
    }

    /**
     * Deduct stock from source
     */
    protected function deductSourceStock($despatch)
    {
        foreach ($despatch->details as $detail) {
            if ($despatch->source_type == OutletDespatch::SOURCE_HEAD_OFFICE) {
                // Deduct from Head Office Stock
                $hoStock = HeadOfficeStock::where('product_id', $detail->product_id)
                    ->orderBy('id', 'desc')
                    ->first();

                if (!$hoStock) {
                    throw new \Exception("Head office stock not found for product ID: {$detail->product_id}");
                }

                if ($hoStock->current_balance < $detail->despatch_qty) {
                    throw new \Exception("Insufficient head office stock for product ID: {$detail->product_id}");
                }

                $previousBalance = $hoStock->current_balance;
                $newBalance = $previousBalance - $detail->despatch_qty;

                $hoStock->update([
                    'previous_balance' => $previousBalance,
                    'quantity' => $newBalance,
                    'current_balance' => $newBalance,
                ]);

                // Log in HO stock ledger (if exists)
                // You can add HO stock ledger here

            } else {
                // Deduct from Branch Store (Outlet)
                $branchStore = BranchStore::where('product_id', $detail->product_id)
                    ->where('outlet_id', $despatch->source_outlet_id)
                    ->first();

                if (!$branchStore) {
                    throw new \Exception("Branch store not found for outlet ID: {$despatch->source_outlet_id}, product ID: {$detail->product_id}");
                }

                if ($branchStore->balanceinhand < $detail->despatch_qty) {
                    throw new \Exception("Insufficient stock in source outlet for product ID: {$detail->product_id}");
                }

                $previousBalance = $branchStore->balanceinhand;
                $branchStore->stockbalancebefore = $previousBalance;
                $branchStore->balanceinhand = $previousBalance - $detail->despatch_qty;
                $branchStore->stockbalanceafter = $branchStore->balanceinhand;
                $branchStore->save();
            }

            // Create OUT ledger entry
            $this->createLedgerEntry([
                'entry_date' => $despatch->despatch_date,
                'outlet_id' => $despatch->source_outlet_id,
                'product_id' => $detail->product_id,
                'table_name' => 'outlet_despatches',
                'unique_id' => $despatch->id,
                'in_qty' => 0,
                'out_qty' => $detail->despatch_qty,
                'balance_before' => $previousBalance ?? 0,
                'balance_after' => $newBalance ?? ($previousBalance - $detail->despatch_qty),
                'type' => OutletStockLedger::TYPE_OUT,
                'user_id' => Auth::id(),
                'remarks' => "Despatched via despatch #{$despatch->despatch_no}",
            ]);
        }

        // Update despatch status to in-transit
        $despatch->update(['status' => OutletDespatch::STATUS_IN_TRANSIT]);
    }

    /**
     * Update request status based on despatch and receive status
     */
    protected function updateRequestStatus($requestId)
    {
        $request = OutletRequest::findOrFail($requestId);
        $details = $request->details;

        $allDespatched = true;
        $allReceived = true;
        $anyDespatched = false;

        foreach ($details as $detail) {
            if ($detail->despatched_qty < $detail->approved_qty) {
                $allDespatched = false;
            }
            if ($detail->despatched_qty > 0) {
                $anyDespatched = true;
            }
            if ($detail->received_qty < $detail->despatched_qty) {
                $allReceived = false;
            }
        }

        if ($allReceived && $anyDespatched) {
            $status = OutletRequest::STATUS_CLOSED;
        } elseif ($allDespatched) {
            $status = OutletRequest::STATUS_DESPATCHED;
        } elseif ($anyDespatched) {
            $status = OutletRequest::STATUS_DESPATCHED; // Partial despatch
        } else {
            $status = $request->status; // Keep current status
        }

        $request->update(['status' => $status]);
    }

    /**
     * Receive stock at destination outlet
     */
    public function receiveStock($despatchId, array $data)
    {
        return DB::transaction(function () use ($despatchId, $data) {
            $despatch = OutletDespatch::with('details')->findOrFail($despatchId);

            if ($despatch->status == OutletDespatch::STATUS_RECEIVED) {
                throw new \Exception('This despatch is already fully received');
            }

            $receive = OutletReceive::create([
                'receive_no' => $this->generateReceiveNo(),
                'receive_date' => $data['receive_date'] ?? Carbon::now()->format('Y-m-d'),
                'despatch_id' => $despatchId,
                'receiving_outlet_id' => $despatch->dest_outlet_id,
                'received_by' => Auth::id(),
                'status' => OutletReceive::STATUS_PENDING,
                'remarks' => $data['remarks'] ?? null,
                'validity' => 1,
            ]);

            $allReceived = true;
            $anyPartial = false;
            $anyDamage = false;

            foreach ($data['items'] as $item) {
                $despatchDetail = OutletDespatchDetail::findOrFail($item['despatch_detail_id']);
                if ($despatchDetail->despatch_id != $despatchId) {
                    throw new \Exception('Invalid despatch detail');
                }

                $receivedQty = $item['received_qty'];
                $despatchedQty = $despatchDetail->despatch_qty;
                $remaining = $despatchedQty - $despatchDetail->received_qty;

                if ($receivedQty > $remaining) {
                    throw new \Exception("Received quantity exceeds remaining despatch quantity");
                }

                $shortQty = $item['short_qty'] ?? 0;
                $damageQty = $item['damage_qty'] ?? 0;

                // Validate short + damage + received = despatched
                $totalAccounted = $receivedQty + $shortQty + $damageQty;
                if ($totalAccounted > $despatchedQty) {
                    throw new \Exception("Total accounted qty exceeds despatched qty");
                }

                // Create receive detail
                $receiveDetail = OutletReceiveDetail::create([
                    'receive_id' => $receive->id,
                    'despatch_detail_id' => $despatchDetail->id,
                    'product_id' => $despatchDetail->product_id,
                    'despatched_qty' => $despatchedQty,
                    'received_qty' => $receivedQty,
                    'short_qty' => $shortQty,
                    'damage_qty' => $damageQty,
                    'remarks' => $item['remarks'] ?? null,
                    'validity' => 1,
                ]);

                // Update request detail received qty
                if ($despatchDetail->request_detail_id) {
                    $requestDetail = OutletRequestDetail::find($despatchDetail->request_detail_id);
                    if ($requestDetail) {
                        $requestDetail->increment('received_qty', $receivedQty);
                    }
                }

                // Update despatch detail received qty
                $despatchDetail->increment('received_qty', $receivedQty);

                // Add to destination outlet stock
                $this->addToDestinationStock($despatch, $despatchDetail, $receivedQty);

                if ($receivedQty < $despatchedQty) {
                    $allReceived = false;
                    $anyPartial = true;
                }
                if ($damageQty > 0) {
                    $anyDamage = true;
                }
            }

            // Update receive status
            if ($allReceived && !$anyDamage) {
                $receive->status = OutletReceive::STATUS_COMPLETE;
            } elseif ($anyPartial || $anyDamage) {
                $receive->status = $anyDamage ? OutletReceive::STATUS_DISCREPANCY : OutletReceive::STATUS_PARTIAL;
            }
            $receive->save();

            // Update despatch status
            $this->updateDespatchStatus($despatchId);

            // Update request status
            if ($despatch->request_id) {
                $this->updateRequestStatus($despatch->request_id);
            }

            return $receive->load('details');
        });
    }

    /**
     * Add stock to destination outlet
     */
    protected function addToDestinationStock($despatch, $despatchDetail, $receivedQty)
    {
        $branchStore = BranchStore::where('product_id', $despatchDetail->product_id)
            ->where('outlet_id', $despatch->dest_outlet_id)
            ->first();

        if ($branchStore) {
            $previousBalance = $branchStore->balanceinhand;
            $branchStore->stockbalancebefore = $previousBalance;
            $branchStore->balanceinhand = $previousBalance + $receivedQty;
            $branchStore->stockbalanceafter = $branchStore->balanceinhand;
            $branchStore->save();
        } else {
            // Create new branch store entry
            $product = Product::findOrFail($despatchDetail->product_id);
            $branchStore = BranchStore::create([
                'product_id' => $despatchDetail->product_id,
                'entrydate' => Carbon::now()->format('Y-m-d'),
                'balanceinhand' => $receivedQty,
                'stockbalancebefore' => 0,
                'stockbalanceafter' => $receivedQty,
                'sale_price' => $product->sale_price ?? 0,
                'vat_rate' => $product->vat_rate ?? 0,
                'sd_rate' => $product->sd_rate ?? 0,
                'scharge' => $product->scharge ?? 0,
                'outlet_id' => $despatch->dest_outlet_id,
                'opening_balance' => $receivedQty,
                'food_type' => $product->food_type_id,
                'status' => 1,
                'validity' => 1,
            ]);
            $previousBalance = 0;
        }

        // Create IN ledger entry
        $this->createLedgerEntry([
            'entry_date' => Carbon::now()->format('Y-m-d'),
            'outlet_id' => $despatch->dest_outlet_id,
            'product_id' => $despatchDetail->product_id,
            'table_name' => 'outlet_receives',
            'unique_id' => $despatch->id,
            'in_qty' => $receivedQty,
            'out_qty' => 0,
            'balance_before' => $previousBalance ?? 0,
            'balance_after' => $branchStore->balanceinhand,
            'type' => OutletStockLedger::TYPE_IN,
            'user_id' => Auth::id(),
            'remarks' => "Received via despatch #{$despatch->despatch_no}",
        ]);
    }

    /**
     * Update despatch status based on receive status
     */
    protected function updateDespatchStatus($despatchId)
    {
        $despatch = OutletDespatch::with('details')->findOrFail($despatchId);

        $allReceived = true;
        $anyReceived = false;

        foreach ($despatch->details as $detail) {
            if ($detail->received_qty > 0) {
                $anyReceived = true;
            }
            if ($detail->received_qty < $detail->despatch_qty) {
                $allReceived = false;
            }
        }

        if ($allReceived && $anyReceived) {
            $status = OutletDespatch::STATUS_RECEIVED;
        } elseif ($anyReceived) {
            $status = OutletDespatch::STATUS_PARTIAL_RECEIVED;
        } else {
            $status = OutletDespatch::STATUS_IN_TRANSIT;
        }

        $despatch->update(['status' => $status]);
    }

    /**
     * Create stock ledger entry
     */
    protected function createLedgerEntry(array $data)
    {
        return OutletStockLedger::create($data);
    }

    /**
     * Get available stock for an outlet
     */
    public function getOutletStock($outletId, $productId = null)
    {
        $query = BranchStore::where('outlet_id', $outletId)
            ->where('validity', 1);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->get();
    }

    /**
     * Get head office stock
     */
    public function getHeadOfficeStock($productId = null)
    {
        $query = HeadOfficeStock::orderBy('id', 'desc');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->first();
    }

    /**
     * Check if outlet has sufficient stock for transfer
     */
    public function checkOutletStockAvailability($outletId, $productId, $quantity)
    {
        $branchStore = BranchStore::where('outlet_id', $outletId)
            ->where('product_id', $productId)
            ->first();

        if (!$branchStore) {
            return false;
        }

        return $branchStore->balanceinhand >= $quantity;
    }
}
