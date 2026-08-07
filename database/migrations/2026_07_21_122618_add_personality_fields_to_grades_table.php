<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (!Schema::hasColumn('grades', 'total_points')) {
                $table->integer('total_points')->nullable()->after('grade');
            }

            if (!Schema::hasColumn('grades', 'max_points')) {
                $table->integer('max_points')->nullable()->after('total_points');
            }

            if (!Schema::hasColumn('grades', 'status')) {
                $table->enum('status', ['in_progress', 'completed', 'baik', 'kurang_baik'])->default('in_progress')->after('max_points');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
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
