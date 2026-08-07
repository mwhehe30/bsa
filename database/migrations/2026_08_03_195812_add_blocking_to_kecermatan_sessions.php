<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kecermatan_sessions', function (Blueprint $table) {
            $table->boolean('is_blocked')->default(false)->after('status');
            $table->integer('violation_count')->default(0)->after('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('kecermatan_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_blocked', 'violation_count']);
        });
    }
};
