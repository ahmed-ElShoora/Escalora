<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('admin')->after('email');
            $table->string('status')->default('active')->after('role');
            $table->foreignId('company_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->foreignId('workspace_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('invitation_token', 64)->nullable()->unique()->after('remember_token');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');

            $table->index(['company_id', 'role']);
            $table->index(['workspace_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'role']);
            $table->dropIndex(['workspace_id', 'role']);
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropUnique(['invitation_token']);
            $table->dropColumn([
                'role',
                'status',
                'invitation_token',
                'invitation_expires_at',
            ]);
        });
    }
};
