<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code')->unique();
            $table->string('event_name');
            $table->string('organizer_name');
            $table->string('organizer_contact');
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->unsignedInteger('participant_count')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['Diajukan', 'Dikonfirmasi', 'Berlangsung', 'Selesai', 'Dibatalkan'])->default('Diajukan');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['area_id', 'start_time', 'end_time']);
        });

        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('room_bookings')->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['booking_id', 'created_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('task_type', ['rutin', 'komplain', 'peminjaman', 'inspeksi_korektif']);
            $table->enum('priority', ['Rendah', 'Sedang', 'Tinggi', 'Darurat']);
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->enum('status', ['Dijadwalkan', 'Belum Dikerjakan', 'Proses', 'Pemeriksaan', 'Selesai'])->default('Belum Dikerjakan');
            $table->boolean('is_cancelled')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['status', 'task_type', 'area_id']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained()->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();
            $table->unique(['task_id', 'personnel_id']);
        });

        Schema::create('task_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });

        Schema::create('task_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->restrictOnDelete();
            $table->boolean('is_approved');
            $table->text('notes')->nullable();
            $table->string('evidence_photo')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_inspections');
        Schema::dropIfExists('task_status_histories');
        Schema::dropIfExists('task_assignees');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('booking_status_histories');
        Schema::dropIfExists('room_bookings');
    }
};
