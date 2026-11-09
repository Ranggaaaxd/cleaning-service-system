<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->string('reporter_name');
            $table->string('reporter_contact')->nullable();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('complaint_categories')->restrictOnDelete();
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->enum('priority', ['Rendah', 'Sedang', 'Tinggi', 'Darurat']);
            $table->enum('status', ['Baru', 'Diverifikasi', 'Diproses', 'Menunggu Pemeriksaan', 'Selesai', 'Ditolak/Dibatalkan'])->default('Baru');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority']);
            $table->index(['area_id', 'created_at']);
        });

        Schema::create('complaint_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['complaint_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_status_histories');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('complaint_categories');
    }
};
