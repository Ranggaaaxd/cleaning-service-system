<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringInspection extends Model
{
    public const OVERALL_STATUSES = ['Sangat Bersih', 'Cukup', 'Kotor/Perlu Tindakan'];

    protected $fillable = [
        'code',
        'area_id',
        'inspector_id',
        'inspection_type',
        'inspection_date',
        'inspection_time',
        'overall_status',
        'notes',
    ];

    protected function casts(): array
    {
        return ['inspection_date' => 'date'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InspectionDetail::class, 'inspection_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(InspectionFinding::class, 'inspection_id');
    }
}
