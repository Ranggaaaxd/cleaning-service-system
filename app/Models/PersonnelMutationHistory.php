<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelMutationHistory extends Model
{
    protected $fillable = [
        'personnel_id',
        'from_floor_id',
        'to_floor_id',
        'reason',
        'mutated_by',
        'effective_date',
    ];

    protected function casts(): array
    {
        return ['effective_date' => 'date'];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function fromFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'from_floor_id');
    }

    public function toFloor(): BelongsTo
    {
        return $this->belongsTo(Floor::class, 'to_floor_id');
    }

    public function mutatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mutated_by');
    }
}
