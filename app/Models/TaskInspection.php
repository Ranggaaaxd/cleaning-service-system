<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskInspection extends Model
{
    protected $fillable = ['task_id', 'inspector_id', 'is_approved', 'notes', 'evidence_photo'];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
