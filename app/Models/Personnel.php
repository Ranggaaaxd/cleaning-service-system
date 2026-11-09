<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personnel extends Model
{
    use SoftDeletes;

    public const STATUSES = ['Aktif', 'Cuti', 'Nonaktif', 'Resign', 'Kontrak Berakhir'];

    protected $fillable = [
        'nik',
        'name',
        'gender',
        'phone',
        'address',
        'position_id',
        'floor_id',
        'status',
        'join_date',
        'photo_path',
    ];

    protected function casts(): array
    {
        return ['join_date' => 'date'];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PersonnelStatusHistory::class)->latest();
    }

    public function mutationHistories(): HasMany
    {
        return $this->hasMany(PersonnelMutationHistory::class)
            ->with(['fromFloor', 'toFloor', 'mutatedBy'])
            ->latest('effective_date');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(PersonnelChangeRequest::class)->latest();
    }
}
