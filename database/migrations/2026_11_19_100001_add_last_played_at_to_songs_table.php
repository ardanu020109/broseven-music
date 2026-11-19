<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->timestamp('last_played_at')->nullable()->after('track_number');
            $table->index(['user_id', 'last_played_at']);
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'last_played_at']);
            $table->dropColumn('last_played_at');
        });
    }
};