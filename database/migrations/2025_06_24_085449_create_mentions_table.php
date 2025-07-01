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
        Schema::create('mentions', function (Blueprint $table) {
            // プライマリキー
            $table->uuid('id')->primary();

            // 外部キー (postsテーブル)
            $table->string('did', 255)->index()->comment('postsテーブルの外部キー');
            $table->foreign('did')
                ->references('did')->on('posts')
                ->onDelete('cascade');

            // メンション情報
            $table->string('mentioned_did', 255)->index()->comment('メンションされたユーザーのDID');
            $table->string('mentioned_handle', 255)->index()->comment('メンションされたユーザーのハンドル');

            // レコード作成・更新日時
            $table->timestamps(); // created_at, updated_at

            // 必要に応じてインデックス
            // $table->index('mentioned_did');
            // $table->index('mentioned_handle');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('mentions');
    }
};
