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
        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'exam_session_id')) {
                $table->dropForeign(['exam_session_id']);
                $table->dropColumn('exam_session_id');
            }

            if (!Schema::hasColumn('grades', 'attempt_number')) {
                $table->integer('attempt_number')->default(1)->after('student_id');
            }

            if (!Schema::hasColumn('grades', 'total_points')) {
                $table->integer('total_points')->default(0)->after('grade');
            }

            if (!Schema::hasColumn('grades', 'max_points')) {
                $table->integer('max_points')->default(0)->after('total_points');
            }

            if (!Schema::hasColumn('grades', 'status')) {
                $table->enum('status', ['in_progress', 'completed', 'baik', 'kurang_baik'])->default('in_progress')->after('max_points');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (!Schema::hasColumn('grades', 'exam_session_id')) {
                $table->foreignId('exam_session_id')->after('exam_id')->constrained('exam_sessions')->cascadeOnDelete();
            }

            if (Schema::hasColumn('grades', 'attempt_number')) {
                $table->dropColumn('attempt_number');
            }

            if (Schema::hasColumn('grades', 'total_points')) {
                $table->dropColumn('total_points');
            }

            if (Schema::hasColumn('grades', 'max_points')) {
                $table->dropColumn('max_points');
            }

            if (Schema::hasColumn('grades', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
