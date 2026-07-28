<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tribute_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tribute_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['image', 'audio', 'video', 'document'])->index();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tribute_media');
    }
};
