<?php

namespace App\Models;

use App\Casts\TimeCast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advert extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['type', 'media_url'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['name', 'media_path', 'date_from', 'date_to', 'time_from', 'time_to', 'deleted_at', 'created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_from' => 'date:Y-m-d',
            'date_to' => 'date:Y-m-d',
            'time_from' => TimeCast::class,
            'time_to' => TimeCast::class
        ];
    }

    protected function getMediaUrlAttribute(): string
    {
        return asset("assets/adverts/" . $this->media_path);

    }

    /**
     * The roles that belong to the user.
     */
    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('order');
        });
    }

    public function getTypeAttribute()
    {
        $extension = pathinfo($this->media_path, PATHINFO_EXTENSION);
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return 'IMAGE';
        } else {
            return 'VIDEO';
        }
    }
}
