<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kecermatan_sessions', function (Blueprint $table) {
            $table->timestamp('column_start_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('kecermatan_sessions', function (Blueprint $table) {
            $table->dropColumn('column_start_time');
        });
    }
};
