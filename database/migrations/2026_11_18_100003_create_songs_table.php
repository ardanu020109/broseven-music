<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('album_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('artist_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('audio_path', 500);
            $table->unsignedInteger('duration')->nullable(); // dalam detik
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);
            $table->longText('lyrics')->nullable();
            $table->unsignedSmallInteger('track_number')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'title']);
            $table->index(['album_id', 'track_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};