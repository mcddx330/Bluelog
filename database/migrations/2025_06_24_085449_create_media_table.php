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
        Schema::create('media', function (Blueprint $table) {
            // プライマリキー
            $table->uuid('id')->primary();

            // 外部キー（postsテーブル）
            $table->string('post_cid')->index()->comment('対象のポストcid');
            $table->foreign('post_cid')
                ->references('cid')->on('posts')
                ->onDelete('cascade');

            // メディア情報
            $table->string('type', 50)->index()->comment('メディアタイプ (image, video等)');
            $table->text('alt_text')->nullable()->comment('代替テキスト');
            $table->string('size')->comment('ファイルサイズ');
            $table->string('mime')->comment('MIME Type');
            $table->string('fullsize_url', 255)->comment('フルサイズ画像URL');
            $table->string('thumbnail_url', 255)->nullable()->comment('サムネイルURL');
            $table->unsignedInteger('aspect_ratio_width')->nullable()->comment('アスペクト比（幅）');
            $table->unsignedInteger('aspect_ratio_height')->nullable()->comment('アスペクト比（高さ）');

            // レコード作成・更新日時
            $table->timestamps(); // created_at, updated_at

            // 必要に応じてインデックス
            // $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('media');
    }
};
