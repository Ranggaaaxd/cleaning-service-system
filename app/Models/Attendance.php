<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUSES = ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Cuti', 'WFH'];

    protected $fillable = [
        'import_id',
        'personnel_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'late_minutes',
        'early_leave_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function attendanceImport(): BelongsTo
    {
        return $this->belongsTo(AttendanceImport::class, 'import_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }
}
