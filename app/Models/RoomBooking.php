<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RoomBooking extends Model
{
    public const STATUSES = ['Diajukan', 'Dikonfirmasi', 'Berlangsung', 'Selesai', 'Dibatalkan'];

    protected $fillable = [
        'booking_code',
        'event_name',
        'organizer_name',
        'organizer_contact',
        'area_id',
        'start_time',
        'end_time',
        'participant_count',
        'notes',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class, 'booking_id')->with('changedBy')->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'source');
    }
}
