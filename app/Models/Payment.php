<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancelled_at', 
        'invoice_number', 
        'payment_provider', 
        'transaction_reference',
        'response_body',
        'invoice_response',
        'signature_header',
        'callback_at',
        'status'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'callback_at' => 'datetime:Y-m-d H:i:s',
            'cancelled_at' => 'datetime:Y-m-d H:i:s',
            'expiry_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PaymentProducts::class);
    }
}
