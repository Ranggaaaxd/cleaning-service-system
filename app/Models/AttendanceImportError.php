<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImportError extends Model
{
    protected $fillable = ['import_id', 'raw_nik', 'raw_name', 'row_number', 'error_message'];

    public function attendanceImport(): BelongsTo
    {
        return $this->belongsTo(AttendanceImport::class, 'import_id');
    }
}
