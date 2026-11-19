<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('cover_image')->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};