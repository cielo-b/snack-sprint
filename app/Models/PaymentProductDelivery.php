<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProductDelivery extends Model
{
    use HasFactory;

    protected $fillable = ['payment_product_id', 'status_code', 'lane_id', 'lane_quantity', 'embody_status', 'state'];

    protected $table = 'payment_products_delivery_status';

    public function inventoryState(): BelongsTo
    {
        return $this->belongsTo(InventoryState::class, 'lane_id', 'lane_id');
    }
}
