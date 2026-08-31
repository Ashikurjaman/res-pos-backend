<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StockBatch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'farm_id',
        'item_id',
        'warehouse_id',
        'batch_no',
        'manufacturing_date',
        'expiry_date',
        'purchase_date',
        'initial_qty',
        'remaining_qty',
        'unit_cost',
        'purchase_id',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'farm_id' => 'integer',
        'item_id' => 'integer',
        'warehouse_id' => 'integer',
        'manufacturing_date' => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
        'purchase_date' => 'date:Y-m-d',
        'initial_qty' => 'float',
        'remaining_qty' => 'float',
        'unit_cost' => 'float',
        'purchase_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'manufacturing_date',
        'expiry_date',
        'purchase_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_quantity',
        'is_expired',
        'is_low_stock',
        'days_until_expiry',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the farm that owns the batch.
     */
    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Get the item that owns the batch.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the warehouse that owns the batch.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the purchase that owns the batch.
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the user who created the batch.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the batch.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the stock ledger entries for this batch.
     */
    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    // ==================== SCOPES ====================

    /**
     * Scope a query to only include active batches.
     */
    public function scopeActive($query)
    {
        return $query->where('remaining_qty', '>', 0);
    }

    /**
     * Scope a query to search for batches.
     */
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('batch_no', 'like', "%{$term}%")
                ->orWhereHas('item', function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                })
                ->orWhereHas('warehouse', function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%");
                })
                ->orWhereHas('farm', function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Scope a query to only include batches for a specific item.
     */
    public function scopeForItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    /**
     * Scope a query to only include batches for a specific warehouse.
     */
    public function scopeForWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope a query to only include batches for a specific farm.
     */
    public function scopeForFarm($query, $farmId)
    {
        return $query->where('farm_id', $farmId);
    }

    /**
     * Scope a query to only include expired batches.
     */
    public function scopeExpired($query)
    {
        return $query->whereDate('expiry_date', '<', now());
    }

    /**
     * Scope a query to only include non-expired batches.
     */
    public function scopeNotExpired($query)
    {
        return $query->whereDate('expiry_date', '>=', now());
    }

    /**
     * Scope a query to only include batches expiring soon.
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereDate('expiry_date', '>=', now())
            ->whereDate('expiry_date', '<=', now()->addDays($days));
    }

    /**
     * Scope a query to only include low stock batches.
     */
    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('remaining_qty', '<=', $threshold)
            ->where('remaining_qty', '>', 0);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get the formatted quantity attribute.
     */
    public function getFormattedQuantityAttribute(): string
    {
        return number_format($this->remaining_qty, 2) . ' / ' . number_format($this->initial_qty, 2);
    }

    /**
     * Get the formatted remaining quantity attribute.
     */
    public function getFormattedRemainingAttribute(): string
    {
        return number_format($this->remaining_qty, 2);
    }

    /**
     * Get the formatted initial quantity attribute.
     */
    public function getFormattedInitialAttribute(): string
    {
        return number_format($this->initial_qty, 2);
    }

    /**
     * Get the formatted unit cost attribute.
     */
    public function getFormattedUnitCostAttribute(): string
    {
        return number_format($this->unit_cost, 2);
    }

    /**
     * Get the total value of the batch.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->remaining_qty * $this->unit_cost;
    }

    /**
     * Get the formatted total value attribute.
     */
    public function getFormattedTotalValueAttribute(): string
    {
        return number_format($this->total_value, 2);
    }

    /**
     * Get the usage percentage attribute.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->initial_qty == 0) {
            return 0;
        }
        return round((($this->initial_qty - $this->remaining_qty) / $this->initial_qty) * 100, 2);
    }

    /**
     * Get the formatted usage percentage attribute.
     */
    public function getFormattedUsagePercentageAttribute(): string
    {
        return $this->usage_percentage . '%';
    }

    /**
     * Check if the batch is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the batch is low stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->remaining_qty <= 10 && $this->remaining_qty > 0;
    }

    /**
     * Get the days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        $days = now()->diffInDays($this->expiry_date, false);
        return $days < 0 ? 0 : (int) $days;
    }

    /**
     * Get the batch status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->remaining_qty <= 0) {
            return 'Out of Stock';
        }

        if ($this->is_expired) {
            return 'Expired';
        }

        if ($this->days_until_expiry !== null && $this->days_until_expiry <= 30) {
            return 'Expiring Soon';
        }

        if ($this->is_low_stock) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    /**
     * Get the status badge color.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'Out of Stock' => 'danger',
            'Expired' => 'dark',
            'Expiring Soon' => 'warning',
            'Low Stock' => 'warning',
            default => 'success',
        };
    }

    /**
     * Set the batch number automatically if not provided.
     */
    public function setBatchNoAttribute($value)
    {
        if (empty($value)) {
            $value = 'BATCH-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
        }
        $this->attributes['batch_no'] = $value;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if the batch has enough quantity.
     */
    public function hasEnoughQuantity(float $quantity): bool
    {
        return $this->remaining_qty >= $quantity;
    }

    /**
     * Decrease the remaining quantity.
     */
    public function decreaseQuantity(float $quantity): bool
    {
        if (!$this->hasEnoughQuantity($quantity)) {
            return false;
        }

        $this->remaining_qty -= $quantity;
        return $this->save();
    }

    /**
     * Increase the remaining quantity.
     */
    public function increaseQuantity(float $quantity): bool
    {
        $this->remaining_qty += $quantity;
        return $this->save();
    }

    /**
     * Update the remaining quantity.
     */
    public function updateRemainingQuantity(float $newQuantity): bool
    {
        if ($newQuantity < 0) {
            return false;
        }

        $this->remaining_qty = $newQuantity;
        return $this->save();
    }

    /**
     * Get the remaining quantity in the base unit.
     */
    public function getRemainingInBaseUnit(): float
    {
        return $this->remaining_qty;
    }

    /**
     * Get the batch identifier string.
     */
    public function getIdentifierAttribute(): string
    {
        return $this->batch_no . ' - ' . ($this->item?->name ?? 'Unknown Item');
    }

    /**
     * Check if the batch can be used.
     */
    public function isUsable(): bool
    {
        return $this->remaining_qty > 0
            && (!$this->expiry_date || !$this->expiry_date->isPast());
    }

    // ==================== BOOT METHOD ====================

    protected static function boot()
    {
        parent::boot();

        // Auto-generate batch number if not provided
        static::creating(function ($model) {
            if (empty($model->batch_no)) {
                $model->batch_no = 'BATCH-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
            }
        });

        // Ensure remaining_qty doesn't exceed initial_qty
        static::saving(function ($model) {
            if ($model->remaining_qty > $model->initial_qty) {
                $model->remaining_qty = $model->initial_qty;
            }
        });
    }
}
