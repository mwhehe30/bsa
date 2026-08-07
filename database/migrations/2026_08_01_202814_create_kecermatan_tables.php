<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table 1: kecermatan_exams (Link ke exam biasa)
        Schema::create('kecermatan_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->cascadeOnDelete();
            $table->string('title');
            $table->integer('duration')->default(600); // 10 minutes
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('is_active');
            $table->unique('exam_id'); // 1 exam = 1 kecermatan_exam
        });

        // Table 2: kecermatan_master_questions
        Schema::create('kecermatan_master_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecermatan_exam_id')->constrained('kecermatan_exams')->cascadeOnDelete();
            $table->enum('exam_type', ['huruf', 'angka', 'simbol', 'gambar']);
            $table->integer('column_number'); // 1-10
            $table->integer('question_number'); // 1-50
            $table->json('reference_sequence'); // 5 items
            $table->json('question_sequence'); // 4 items
            $table->integer('missing_position'); // 0-4
            $table->string('missing_item', 10);
            $table->char('correct_answer', 1); // A/B/C/D/E
            $table->timestamps();
            
            $table->index(['kecermatan_exam_id', 'exam_type', 'column_number'], 'idx_master_exam_type_col');
            $table->index(['kecermatan_exam_id', 'exam_type'], 'idx_master_exam_type');
        });

        // Table 3: kecermatan_sessions
        Schema::create('kecermatan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecermatan_exam_id')->constrained('kecermatan_exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('exam_type', ['huruf', 'angka', 'simbol', 'gambar']);
            $table->integer('current_column')->default(1);
            $table->integer('current_question')->default(1);
            $table->enum('status', ['preparing', 'in_progress', 'completed', 'abandoned'])->default('preparing');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('total_score')->default(0);
            $table->integer('total_correct')->default(0);
            $table->integer('total_wrong')->default(0);
            $table->integer('total_unanswered')->default(0);
            $table->timestamps();
            
            $table->unique(['student_id', 'kecermatan_exam_id']);
            $table->index(['student_id', 'status']);
        });

        // Table 4: kecermatan_questions
        Schema::create('kecermatan_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('kecermatan_sessions')->cascadeOnDelete();
            $table->foreignId('master_question_id')->nullable()->constrained('kecermatan_master_questions')->nullOnDelete();
            $table->integer('column_number');
            $table->integer('question_number');
            $table->integer('shuffled_order'); // urutan setelah shuffle
            $table->json('reference_sequence');
            $table->json('question_sequence');
            $table->integer('missing_position');
            $table->string('missing_item', 10);
            $table->char('correct_answer', 1);
            $table->char('student_answer', 1)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('time_spent')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            
            $table->index(['session_id', 'column_number', 'question_number'], 'idx_questions_session_col_q');
            $table->index(['session_id', 'column_number'], 'idx_questions_session_col');
            $table->index('session_id', 'idx_questions_session');
        });

        // Table 5: kecermatan_results
        Schema::create('kecermatan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('kecermatan_sessions')->cascadeOnDelete();
            $table->integer('column_number');
            $table->integer('total_questions')->default(50);
            $table->integer('correct_count')->default(0);
            $table->integer('wrong_count')->default(0);
            $table->integer('unanswered_count')->default(0);
            $table->integer('time_spent')->default(0);
            $table->timestamps();
            
            $table->unique(['session_id', 'column_number'], 'uniq_results_session_col');
            $table->index(['session_id', 'column_number'], 'idx_results_session_col');
        });

        // Table 6: kecermatan_violations
        Schema::create('kecermatan_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('kecermatan_sessions')->cascadeOnDelete();
            $table->enum('violation_type', ['exit_fullscreen', 'tab_switch', 'browser_blur']);
            $table->timestamp('violation_time');
            $table->integer('column_number')->nullable();
            $table->integer('question_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['session_id', 'violation_time'], 'idx_violations_session_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kecermatan_violations');
        Schema::dropIfExists('kecermatan_results');
        Schema::dropIfExists('kecermatan_questions');
        Schema::dropIfExists('kecermatan_sessions');
        Schema::dropIfExists('kecermatan_master_questions');
        Schema::dropIfExists('kecermatan_exams');
    }
};
