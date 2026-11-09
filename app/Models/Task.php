<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    public const TYPES = ['rutin', 'komplain', 'peminjaman', 'inspeksi_korektif'];

    public const PRIORITIES = ['Rendah', 'Sedang', 'Tinggi', 'Darurat'];

    public const STATUSES = ['Dijadwalkan', 'Belum Dikerjakan', 'Proses', 'Pemeriksaan', 'Selesai'];

    protected $fillable = [
        'task_code',
        'title',
        'description',
        'task_type',
        'priority',
        'area_id',
        'start_date',
        'due_date',
        'due_time',
        'status',
        'is_cancelled',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'source_id',
        'source_type',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'is_cancelled' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Personnel::class, 'task_assignees')
            ->withPivot('assigned_at')
            ->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class)->with('changedBy')->latest();
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(TaskInspection::class)->with('inspector')->latest();
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
