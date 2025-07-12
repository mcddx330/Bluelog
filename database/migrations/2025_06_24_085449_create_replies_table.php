<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up() {
        Schema::create('replies', function (Blueprint $table) {
            // プライマリキー
            $table->uuid('id')->primary();

            // 外部キー (postsテーブル)
            $table->foreignUuid('post_id')->constrained('posts')->cascadeOnDelete()->comment('posts.id');

            // リプライ情報
            $table->string('reply_to_did', 255)->index()->comment('リプライされたユーザーのDID');
            $table->string('reply_to_handle', 255)->index()->comment('リプライされたユーザーのハンドル');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('replies');
    }
};
