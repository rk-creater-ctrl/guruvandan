<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->enum('tribute_type', [
                'thank_you_message',
                'letter',
                'poem',
                'drawing',
                'photo_memory',
                'audio_message',
                'video_wish',
                'greeting_card',
            ])->index();
            $table->string('title');
            $table->longText('message')->nullable();
            $table->enum('language', ['english', 'hindi', 'hinglish', 'sanskrit_quote'])->default('english')->index();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();

            $table->index(['teacher_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tributes');
    }
};
