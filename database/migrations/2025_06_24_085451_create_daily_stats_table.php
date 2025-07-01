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
        Schema::create('daily_stats', function (Blueprint $table) {
            // プライマリキー
            $table->id();

            // 外部キー（usersテーブル）
            $table->string('did', 255)->index()->comment('usersテーブルの外部キー');
            $table->foreign('did')
                ->references('did')->on('users')
                ->onDelete('cascade');

            // 日付
            $table->string('date')->comment('統計対象の日付 (YYYY-MM-DD)');

            // 日別カウント情報
            $table->unsignedBigInteger('posts_count')->default(0)->comment('その日のポスト数');
            $table->unsignedBigInteger('likes_count')->default(0)->comment('その日のいいね数（被いいね数ではなく、自分が行ったいいね）');
            $table->unsignedBigInteger('replies_count')->default(0)->comment('その日のリプライ数');
            $table->unsignedBigInteger('reposts_count')->default(0)->comment('その日のリポスト数');
            $table->unsignedBigInteger('mentions_count')->default(0)->comment('その日のメンション数');

            // レコード作成・更新日時
            $table->timestamps(); // created_at, updated_at

            // ユニーク制約
            $table->unique(['did', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('daily_stats');
    }
};
