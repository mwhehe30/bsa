<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('discussion_file_path')->nullable()->after('description');
            $table->string('discussion_file_name')->nullable()->after('discussion_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['discussion_file_path', 'discussion_file_name']);
        });
    }
};
