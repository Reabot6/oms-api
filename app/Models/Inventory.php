<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'last_restocked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_restocked_at' => 'datetime',
    ];

    /**
     * Get the product that owns the inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the product variant that owns the inventory.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get available quantity.
     * Available quantity should never be negative.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Check if inventory is low.
     */
    public function isLowStock(): bool
    {
        return $this->available_quantity <= $this->low_stock_threshold;
    }

    /**
     * Check if has sufficient quantity.
     */
    public function hasSufficientQuantity(int $requested): bool
    {
        return $this->available_quantity >= $requested;
    }

    /**
     * Reserve quantity.
     * Uses SELECT FOR UPDATE to prevent overselling under concurrent requests.
     * Must be called inside a DB::transaction().
     */
    public function reserve(int $quantity): bool
    {
        $fresh = static::where('id', $this->id)->lockForUpdate()->first();
        if (!$fresh || $fresh->available_quantity < $quantity) {
            return false;
        }
        $result = $fresh->increment('reserved_quantity', $quantity);
        $this->refresh();
        return $result > 0;
    }

    /**
     * Release reserved quantity back to the available pool.
     * Call on order cancellation BEFORE stock was physically shipped.
     */
    public function release(int $quantity): bool
    {
        $fresh = static::where('id', $this->id)->lockForUpdate()->first();
        if (!$fresh) { return false; }
        $safe = min($quantity, $fresh->reserved_quantity);
        $result = $fresh->decrement('reserved_quantity', $safe);
        $this->refresh();
        return $result > 0;
    }

    /**
     * Deduct quantity — converts a reservation into a real stock reduction.
     * Call when order moves to processing/shipped.
     */
    public function deduct(int $quantity): bool
    {
        $fresh = static::where('id', $this->id)->lockForUpdate()->first();
        if (!$fresh) { return false; }
        $fresh->decrement('quantity', $quantity);
        $fresh->decrement('reserved_quantity', $quantity);
        $this->refresh();
        return true;
    }

    /**
     * Restore quantity — add stock back after a physical return.
     * Do NOT call on cancellation; use release() instead.
     */
    public function restore(int $quantity): bool
    {
        $result = static::where('id', $this->id)->lockForUpdate()->first()
            ?->increment('quantity', $quantity);
        $this->refresh();
        return $result > 0;
    }
}
