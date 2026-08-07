<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->integer('duration');
            $table->text('description')->nullable();
            $table->enum('random_question', ['Y', 'N'])->default('Y');
            $table->enum('random_answer', ['Y', 'N'])->default('Y');
            $table->enum('show_answer', ['Y', 'N'])->default('N');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
