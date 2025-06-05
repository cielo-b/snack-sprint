<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryState extends Model
{
    use HasFactory;

    protected $table = 'inventory_state';

    protected $fillable = ['machine_id', 'lane_id', 'product_id', 'quantity', 'max_quantity'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
