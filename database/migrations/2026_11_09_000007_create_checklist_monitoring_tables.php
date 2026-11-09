<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('area_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['name', 'area_type']);
        });

        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->string('task_name');
            $table->unsignedInteger('order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['template_id', 'order']);
        });

        Schema::create('monitoring_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->restrictOnDelete();
            $table->enum('inspection_type', ['rutin', 'mendadak']);
            $table->date('inspection_date');
            $table->time('inspection_time');
            $table->enum('overall_status', ['Sangat Bersih', 'Cukup', 'Kotor/Perlu Tindakan']);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['inspection_date', 'area_id']);
        });

        Schema::create('inspection_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('monitoring_inspections')->cascadeOnDelete();
            $table->foreignId('checklist_item_id')->nullable()->constrained('checklist_items')->nullOnDelete();
            $table->string('item_snapshot_text');
            $table->enum('status', ['Bersih', 'Kotor', 'Rusak', 'Tidak Berlaku']);
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['inspection_id', 'status']);
        });

        Schema::create('inspection_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('monitoring_inspections')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->text('description');
            $table->string('evidence_photo')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved'])->default('open');
            $table->foreignId('recheck_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recheck_at')->nullable();
            $table->text('recheck_notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_findings');
        Schema::dropIfExists('inspection_details');
        Schema::dropIfExists('monitoring_inspections');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklist_templates');
    }
};
