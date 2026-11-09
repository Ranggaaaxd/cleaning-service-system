<?php

namespace App\Modules\Task\Services;

use App\Models\Task;
use App\Models\TaskInspection;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function createTask(array $data, array $personnelIds, int $userId): Task
    {
        return DB::transaction(function () use ($data, $personnelIds, $userId): Task {
            $user = User::findOrFail($userId);
            $task = Task::create([
                ...$data,
                'task_code' => $this->generateTaskCode($data['start_date'] ?? today()->toDateString()),
                'status' => $data['status'] ?? 'Belum Dikerjakan',
                'is_cancelled' => false,
                'created_by' => $userId,
            ]);

            if ($personnelIds !== []) {
                $task->assignees()->attach(array_unique($personnelIds), ['assigned_at' => now()]);
            }

            TaskStatusHistory::create([
                'task_id' => $task->id,
                'old_status' => '',
                'new_status' => $task->status,
                'notes' => 'Tugas dibuat.',
                'changed_by' => $userId,
            ]);

            AuditService::log($user, 'task', 'create', $task, newValues: [
                'task_code' => $task->task_code,
                'title' => $task->title,
                'status' => $task->status,
                'assignees' => array_values(array_unique($personnelIds)),
            ]);

            return $task;
        });
    }

    public function updateStatus(Task $task, string $newStatus, ?string $notes, int $userId): Task
    {
        Validator::make(['status' => $newStatus], [
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
        ])->validate();

        return DB::transaction(function () use ($task, $newStatus, $notes, $userId): Task {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $this->ensureTaskCanChange($task);
            $oldStatus = $task->status;

            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages(['status' => 'Status baru harus berbeda dengan status saat ini.']);
            }

            $task->update(['status' => $newStatus]);
            $this->recordStatusChange($task, $oldStatus, $newStatus, $notes, $userId);

            return $task;
        });
    }

    public function cancelTask(Task $task, string $reason, int $userId): Task
    {
        return DB::transaction(function () use ($task, $reason, $userId): Task {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $this->ensureTaskCanChange($task);

            $task->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason,
            ]);

            AuditService::log(
                User::findOrFail($userId),
                'task',
                'cancel',
                $task,
                reason: $reason,
            );

            return $task;
        });
    }

    public function submitInspection(Task $task, bool $isApproved, ?string $notes, ?string $photoPath, int $userId): TaskInspection
    {
        return DB::transaction(function () use ($task, $isApproved, $notes, $photoPath, $userId): TaskInspection {
            $task = Task::query()->lockForUpdate()->findOrFail($task->id);
            $this->ensureTaskCanChange($task);

            if ($task->status !== 'Pemeriksaan') {
                throw ValidationException::withMessages(['inspection' => 'Tugas harus berstatus Pemeriksaan sebelum hasil kerja dinilai.']);
            }

            $inspection = TaskInspection::create([
                'task_id' => $task->id,
                'inspector_id' => $userId,
                'is_approved' => $isApproved,
                'notes' => $notes,
                'evidence_photo' => $photoPath,
            ]);

            $oldStatus = $task->status;
            if ($isApproved) {
                $newStatus = 'Selesai';
            } else {
                $newStatus = $task->statusHistories()
                    ->where('new_status', 'Pemeriksaan')
                    ->latest('id')
                    ->value('old_status');
                if (! in_array($newStatus, ['Proses', 'Belum Dikerjakan'], true)) {
                    $newStatus = 'Proses';
                }
            }

            $task->update(['status' => $newStatus]);
            $this->recordStatusChange($task, $oldStatus, $newStatus, $notes, $userId);
            AuditService::log(
                User::findOrFail($userId),
                'task',
                'inspection',
                $inspection,
                ['task_status' => $oldStatus],
                ['is_approved' => $isApproved, 'task_status' => $newStatus],
                $notes,
            );

            return $inspection;
        });
    }

    private function recordStatusChange(Task $task, string $oldStatus, string $newStatus, ?string $notes, int $userId): void
    {
        TaskStatusHistory::create([
            'task_id' => $task->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $userId,
        ]);

        AuditService::log(
            User::findOrFail($userId),
            'task',
            'status_change',
            $task,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $notes,
        );
    }

    private function ensureTaskCanChange(Task $task): void
    {
        if ($task->is_cancelled) {
            throw ValidationException::withMessages(['task' => 'Tugas yang dibatalkan tidak dapat diubah.']);
        }
    }

    private function generateTaskCode(string $date): string
    {
        $prefix = 'TSK-'.str_replace('-', '', $date).'-';

        do {
            $code = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Task::where('task_code', $code)->exists());

        return $code;
    }
}
