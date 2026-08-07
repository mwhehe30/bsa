<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->integer('question_order');
            $table->string('answer_order');
            $table->integer('answer');
            $table->enum('is_correct', ['Y', 'N'])->default('N');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
