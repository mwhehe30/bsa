<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->unsignedInteger('violation_count')->default(0);
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
        });

        Schema::create('exam_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_group_id')->constrained('exam_groups')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->enum('violation_type', ['tab_switch', 'exit_fullscreen', 'isolated_by_admin']);
            $table->timestamp('violation_time');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
        Schema::dropIfExists('exam_groups');
    }
};
