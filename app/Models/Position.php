<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}
