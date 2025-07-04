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
            $table->string('did', 255)->index()->comment('postsテーブルの外部キー');
            $table->foreign('did')
                ->references('did')->on('posts')
                ->onDelete('cascade');

            // リプライ情報
            $table->string('replied_did', 255)->index()->comment('リプライされたユーザーのDID');
            $table->string('replied_handle', 255)->index()->comment('リプライされたユーザーのハンドル');

            // レコード作成・更新日時
            $table->timestamps(); // created_at, updated_at

            // 必要に応じてインデックス
            // $table->index('replied_did');
            // $table->index('replied_handle');
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
