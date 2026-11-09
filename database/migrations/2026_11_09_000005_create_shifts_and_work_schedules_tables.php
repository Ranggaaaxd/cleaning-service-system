<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('color_badge');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('floor_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['personnel_id', 'date']);
            $table->index(['date', 'floor_id', 'shift_id']);
        });

        Schema::create('wfh_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['personnel_id', 'date']);
            $table->index('date');
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name');
            $table->boolean('is_national')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('wfh_schedules');
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('shifts');
    }
};
