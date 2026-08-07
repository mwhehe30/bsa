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
        Schema::table('kecermatan_sessions', function (Blueprint $table) {
            $table->dropUnique('kecermatan_sessions_student_id_kecermatan_exam_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kecermatan_sessions', function (Blueprint $table) {
            $table->unique(['student_id', 'kecermatan_exam_id']);
        });
    }
};
