<?php

namespace App\Models;

use App\Casts\TimeCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'name', 'location', 'internet_speed', 'last_checked_in_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_checked_in_at' => 'datetime:Y-m-d H:i:s'
        ];
    }

    /**
     * The roles that belong to the user.
     */
    public function adverts(): BelongsToMany
    {
        return $this->belongsToMany(Advert::class);
    }

    public function inventoryState(): HasMany
    {
        return $this->hasMany(InventoryState::class);
    }

}
