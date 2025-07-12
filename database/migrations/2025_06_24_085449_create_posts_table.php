<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up() {
        Schema::create('posts', function (Blueprint $table) {
            // プライマリキー
            $table->uuid('id')->primary();

            // 外部キー（usersテーブル）
            $table->string('did', 255)->index()->comment('Blueskyのユーザー識別子');
            $table->foreign('did')
                ->references('did')->on('users')
                ->onDelete('cascade');

            // Blueskyポスト情報
            $table->string('uri', 255)->unique()->index()->comment('ポストのURI');
            $table->string('cid', 255)->unique()->comment('コンテンツID');
            $table->string('rkey', 255)->index()->comment('レコードキー');
            $table->longText('text')->nullable()->comment('ポスト本文');

            // リプライ／引用
            $table->string('reply_to', 255)->nullable()->index()->comment('リプライ先URI');
            $table->string('quote_of', 255)->nullable()->index()->comment('引用元URI');

            // フラグ
            $table->boolean('is_repost')->default(false)->index()->comment('リポストかどうか');

            // カウント情報
            $table->unsignedBigInteger('likes_count')->default(0)->index()->comment('いいね数');
            $table->unsignedBigInteger('replies_count')->default(0)->index()->comment('リプライ数');
            $table->unsignedBigInteger('reposts_count')->default(0)->index()->comment('リポスト数');

            // タイムスタンプ
            $table->dateTime('posted_at')->nullable()->comment('投稿日時');
            $table->dateTime('indexed_at')->nullable()->comment('インデックス日時');

            // レコード作成・更新日時
            $table->timestamps(); // created_at, updated_at

            // 必要に応じてインデックス
            // $table->index('posted_at');
            // $table->index('indexed_at');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE posts ENGINE=Mroonga');
            DB::statement('CREATE FULLTEXT INDEX posts_text ON posts(text) COMMENT "parser \"TokenMecab\""');
        }
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('posts');
    }
};
