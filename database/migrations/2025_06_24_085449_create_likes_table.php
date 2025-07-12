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
        Schema::create('likes', function (Blueprint $table) {
            // プライマリキー
            $table->uuid('id')->primary();

            // 外部キー (usersテーブル)
            $table->string('did', 255)->index()->comment('usersテーブルの外部キー');
            $table->foreign('did')
                ->references('did')->on('users')
                ->onDelete('cascade');

            // いいね情報
            $table->string('post_uri', 255)->index()->comment('いいねしたポストのURI');
            $table->string('cid', 255)->index()->nullable()->comment('コンテンツID');
            $table->timestamp('post_posted_at')->comment('ポスト投稿日時');
            $table->string('created_by_did', 255)->index()->comment('ポスト作成者のDID');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('likes');
    }
};
