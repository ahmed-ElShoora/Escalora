<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table): void {
            $table->uuid('public_token')->unique()->nullable();
            $table->json('escalation_keywords')->nullable();
            $table->unsignedTinyInteger('max_active_chats')->default(5);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_available')->default(false);
            $table->timestamp('last_seen_at')->nullable();
        });

        DB::table('workspaces')
            ->whereNull('public_token')
            ->chunkById(100, function (Collection $workspaces): void {
                foreach ($workspaces as $workspace) {
                    DB::table('workspaces')
                        ->where('id', $workspace->id)
                        ->update(['public_token' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_available', 'last_seen_at']);
        });

        Schema::table('workspaces', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['public_token', 'escalation_keywords', 'max_active_chats']);
        });
    }
};
