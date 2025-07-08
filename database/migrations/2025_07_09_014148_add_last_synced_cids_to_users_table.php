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
            $table->string('last_synced_post_cid', 255)->nullable()->after('refresh_jwt')->comment('最後に同期した投稿のCID');
            $table->string('last_synced_like_cid', 255)->nullable()->after('last_synced_post_cid')->comment('最後に同期したいのCID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_synced_post_cid');
            $table->dropColumn('last_synced_like_cid');
        });
    }
};
