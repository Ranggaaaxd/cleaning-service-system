<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImport extends Model
{
    protected $fillable = [
        'file_name',
        'imported_by',
        'period_month',
        'period_year',
        'version',
        'is_active',
        'total_rows',
        'total_matched',
        'total_unmatched',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'period_month' => 'integer',
            'period_year' => 'integer',
        ];
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'import_id');
    }

    public function errors(): HasMany
    {
        return $this->hasMany(AttendanceImportError::class, 'import_id')->orderBy('row_number');
    }
}
