<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('total_matched')->default(0);
            $table->unsignedInteger('total_unmatched')->default(0);
            $table->timestamps();
            $table->index(['period_year', 'period_month', 'is_active']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('attendance_imports')->restrictOnDelete();
            $table->foreignId('personnel_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Cuti', 'WFH']);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['personnel_id', 'date', 'import_id']);
            $table->index(['import_id', 'date', 'status']);
        });

        Schema::create('attendance_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('attendance_imports')->cascadeOnDelete();
            $table->string('raw_nik');
            $table->string('raw_name');
            $table->unsignedInteger('row_number');
            $table->text('error_message');
            $table->timestamps();
            $table->index(['import_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_errors');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_imports');
    }
};
