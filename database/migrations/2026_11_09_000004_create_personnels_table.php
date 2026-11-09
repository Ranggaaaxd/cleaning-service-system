<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnels', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique();
            $table->string('name');
            $table->enum('gender', ['L', 'P']);
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['Aktif', 'Cuti', 'Nonaktif', 'Resign', 'Kontrak Berakhir'])->default('Aktif');
            $table->date('join_date');
            $table->string('photo_path')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('personnel_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
            $table->string('old_status');
            $table->string('new_status');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('personnel_mutation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_floor_id')->nullable()->constrained('floors')->nullOnDelete();
            $table->foreignId('to_floor_id')->constrained('floors')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('mutated_by')->constrained('users')->restrictOnDelete();
            $table->date('effective_date');
            $table->timestamps();
        });

        Schema::create('personnel_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->enum('field_name', ['nik', 'name']);
            $table->string('old_value');
            $table->string('new_value');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_change_requests');
        Schema::dropIfExists('personnel_mutation_histories');
        Schema::dropIfExists('personnel_status_histories');
        Schema::dropIfExists('personnels');
    }
};
