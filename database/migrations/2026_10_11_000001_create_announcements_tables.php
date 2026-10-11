<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('announcements_seen_at')->nullable();
        });

        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();
            $table->index(['pinned_at', 'created_at']);
        });

        Schema::create('announcement_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['announcement_id', 'sort_order']);
        });

        Schema::create('announcement_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->timestamps();
            $table->index(['announcement_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_comments');
        Schema::dropIfExists('announcement_images');
        Schema::dropIfExists('announcements');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('announcements_seen_at');
        });
    }
};