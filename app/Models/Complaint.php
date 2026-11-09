<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    public const PRIORITIES = ['Rendah', 'Sedang', 'Tinggi', 'Darurat'];

    public const STATUSES = ['Baru', 'Diverifikasi', 'Diproses', 'Menunggu Pemeriksaan', 'Selesai', 'Ditolak/Dibatalkan'];

    protected $fillable = [
        'ticket_number',
        'reporter_name',
        'reporter_contact',
        'area_id',
        'category_id',
        'description',
        'photo_path',
        'priority',
        'status',
        'verified_by',
        'verified_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ComplaintStatusHistory::class)->with('changedBy')->latest();
    }
}
