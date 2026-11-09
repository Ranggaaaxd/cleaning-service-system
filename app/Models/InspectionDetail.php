<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionDetail extends Model
{
    public const STATUSES = ['Bersih', 'Kotor', 'Rusak', 'Tidak Berlaku'];

    protected $fillable = [
        'inspection_id',
        'checklist_item_id',
        'item_snapshot_text',
        'status',
        'photo_path',
        'notes',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(MonitoringInspection::class, 'inspection_id');
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }
}
