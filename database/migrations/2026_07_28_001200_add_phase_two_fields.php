<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('cover_image_path')->nullable()->after('photo_path');
            $table->string('qualification')->nullable()->after('designation');
            $table->unsignedSmallInteger('years_experience')->nullable()->after('qualification');
        });

        Schema::table('tributes', function (Blueprint $table): void {
            $table->longText('original_message')->nullable()->after('message');
            $table->timestamp('moderator_edited_at')->nullable()->after('approved_at');
            $table->timestamp('resubmitted_at')->nullable()->after('moderator_edited_at');
        });

        Schema::table('tribute_media', function (Blueprint $table): void {
            $table->string('thumbnail_path')->nullable()->after('path');
            $table->unsignedInteger('duration_seconds')->nullable()->after('size');
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->timestamp('revoked_at')->nullable()->after('generated_at');
            $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users')->nullOnDelete();
            $table->string('revocation_reason')->nullable()->after('revoked_by');
        });

        Schema::table('event_schedules', function (Blueprint $table): void {
            $table->string('speaker')->nullable()->after('detail');
            $table->string('location')->nullable()->after('speaker');
            $table->boolean('is_enabled')->default(true)->index()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('event_schedules', function (Blueprint $table): void {
            $table->dropIndex(['is_enabled']);
            $table->dropColumn(['speaker', 'location', 'is_enabled']);
        });

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn(['revoked_at', 'revocation_reason']);
        });

        Schema::table('tribute_media', function (Blueprint $table): void {
            $table->dropColumn(['thumbnail_path', 'duration_seconds']);
        });

        Schema::table('tributes', function (Blueprint $table): void {
            $table->dropColumn(['original_message', 'moderator_edited_at', 'resubmitted_at']);
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn(['cover_image_path', 'qualification', 'years_experience']);
        });
    }
};
