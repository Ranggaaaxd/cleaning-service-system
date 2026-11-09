<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingStatusHistory extends Model
{
    protected $fillable = ['booking_id', 'old_status', 'new_status', 'notes', 'changed_by'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(RoomBooking::class, 'booking_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
