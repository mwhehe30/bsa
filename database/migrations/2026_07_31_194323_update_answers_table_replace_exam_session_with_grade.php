<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'exam_session_id')) {
                $table->dropForeign(['exam_session_id']);
                $table->dropColumn('exam_session_id');
            }

            if (!Schema::hasColumn('answers', 'grade_id')) {
                $table->foreignId('grade_id')->after('exam_id')->constrained('grades')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (!Schema::hasColumn('answers', 'exam_session_id')) {
                $table->foreignId('exam_session_id')->after('exam_id')->constrained('exam_sessions')->cascadeOnDelete();
            }

            if (Schema::hasColumn('answers', 'grade_id')) {
                $table->dropForeign(['grade_id']);
                $table->dropColumn('grade_id');
            }
        });
    }
};
