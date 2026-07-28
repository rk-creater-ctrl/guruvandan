<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->boolean('must_change_password')->default(false)->after('last_login_at');
        });

        DB::table('users')->orderBy('id')->get()->each(function ($user): void {
            $base = Str::of($user->email ?: $user->name)->before('@')->slug('-')->toString() ?: 'user';
            $username = $base;
            $suffix = 2;
            while (DB::table('users')->whereRaw('lower(username) = ?', [strtolower($username)])->where('id', '!=', $user->id)->exists()) {
                $username = $base.'-'.$suffix++;
            }
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
            $table->string('email')->nullable()->change();
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('short_intro')->nullable()->after('cover_image_path');
            $table->unsignedSmallInteger('joining_year')->nullable()->after('years_experience');
            $table->string('location')->nullable()->after('joining_year');
            $table->boolean('is_public')->default(true)->after('is_active')->index();
            $table->timestamp('archived_at')->nullable()->after('is_public')->index();
        });

        DB::table('teachers')->whereNull('short_intro')->update(['short_intro' => DB::raw('banner_title')]);

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropIndex(['subject']);
            $table->dropColumn('subject');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('subject')->nullable()->index();
        });

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn(['short_intro', 'joining_year', 'location', 'is_public', 'archived_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->string('email')->nullable(false)->change();
            $table->dropColumn(['username', 'last_login_at', 'must_change_password']);
        });
    }
};
