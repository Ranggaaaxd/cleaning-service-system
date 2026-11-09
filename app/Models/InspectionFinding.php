<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionFinding extends Model
{
    public const STATUSES = ['open', 'in_progress', 'resolved'];

    protected $fillable = [
        'inspection_id',
        'area_id',
        'description',
        'evidence_photo',
        'status',
        'recheck_by',
        'recheck_at',
        'recheck_notes',
    ];

    protected function casts(): array
    {
        return ['recheck_at' => 'datetime'];
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(MonitoringInspection::class, 'inspection_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function recheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recheck_by');
    }
}
