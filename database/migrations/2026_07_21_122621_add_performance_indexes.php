<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('exam_groups')) {
            Schema::table('exam_groups', function (Blueprint $table) {
                if (!Schema::getConnection()->getSchemaBuilder()->hasIndex('exam_groups', 'idx_exam_groups_student_session')) {
                    $table->index(['student_id', 'exam_id'], 'idx_exam_groups_student_session');
                }
            });
        }

        if (Schema::hasTable('answers')) {
            Schema::table('answers', function (Blueprint $table) {
                if (!Schema::getConnection()->getSchemaBuilder()->hasIndex('answers', 'idx_answers_exam_session_student')) {
                    $table->index(['exam_id', 'student_id'], 'idx_answers_exam_session_student');
                }
            });
        }

        if (Schema::hasTable('exam_violations')) {
            Schema::table('exam_violations', function (Blueprint $table) {
                if (!Schema::getConnection()->getSchemaBuilder()->hasIndex('exam_violations', 'idx_exam_violations_group')) {
                    $table->index('exam_group_id', 'idx_exam_violations_group');
                }
            });
        }

        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::getConnection()->getSchemaBuilder()->hasIndex('grades', 'idx_grades_student_active')) {
                    $table->index(['student_id', 'start_time', 'end_time'], 'idx_grades_student_active');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_groups')) {
            Schema::table('exam_groups', function (Blueprint $table) {
                $table->dropIndex('idx_exam_groups_student_session');
            });
        }

        if (Schema::hasTable('answers')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropIndex('idx_answers_exam_session_student');
            });
        }

        if (Schema::hasTable('exam_violations')) {
            Schema::table('exam_violations', function (Blueprint $table) {
                $table->dropIndex('idx_exam_violations_group');
            });
        }

        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropIndex('idx_grades_student_active');
            });
        }
    }
};
