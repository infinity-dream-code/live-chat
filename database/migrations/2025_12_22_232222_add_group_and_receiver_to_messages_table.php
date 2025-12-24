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
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'group_id')) {
                $table->foreignId('group_id')->nullable()->after('user_id')->constrained('groups')->onDelete('cascade');
            }
            if (!Schema::hasColumn('messages', 'receiver_id')) {
                $table->foreignId('receiver_id')->nullable()->after('group_id')->constrained('users')->onDelete('cascade');
            }
        });

        // Add indexes separately
        Schema::table('messages', function (Blueprint $table) {
            try {
                if (Schema::hasColumn('messages', 'group_id')) {
                    $table->index(['group_id', 'created_at'], 'messages_group_id_created_at_index');
                }
            } catch (\Exception $e) {
                // Index might already exist
            }

            try {
                if (Schema::hasColumn('messages', 'receiver_id')) {
                    $table->index(['receiver_id', 'user_id', 'created_at'], 'messages_receiver_id_user_id_created_at_index');
                }
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
            if (Schema::hasColumn('messages', 'receiver_id')) {
                $table->dropForeign(['receiver_id']);
                $table->dropColumn('receiver_id');
            }
        });
    }
};
