<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    protected $fillable = ['building_id', 'name', 'floor_number', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}
