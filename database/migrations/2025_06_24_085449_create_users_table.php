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
        Schema::create('users', function (Blueprint $table) {
            // Blueskyユーザー情報
            $table->string('did', 255)->primary()->comment('Blueskyのユーザー識別子');
            $table->string('handle', 255)->unique()->index()->comment('ユーザーハンドル (@username)');
            $table->string('display_name', 255)->nullable()->comment('表示名');
            $table->text('description')->nullable()->comment('プロフィール説明');
            $table->string('avatar_url')->nullable()->comment('アバター画像URL');
            $table->string('banner_url')->nullable()->comment('バナー画像URL');

            // カウント情報
            $table->unsignedBigInteger('followers_count')
                ->default(0)
                ->index()
                ->comment('フォロワー数');
            $table->unsignedBigInteger('following_count')
                ->default(0)
                ->index()
                ->comment('フォロー数');
            $table->unsignedBigInteger('posts_count')
                ->default(0)
                ->index()
                ->comment('投稿数');

            // 日時情報
            $table->dateTime('registered_at')->comment('Bluesky登録日');
            $table->dateTime('last_login_at')->comment('最終ログイン日時');
            $table->dateTime('last_fetched_at')->nullable()->comment('最終取得日時');

            // トークン
            $table->text('access_jwt')->comment('アクセストークン');
            $table->text('refresh_jwt')->comment('リフレッシュトークン');

            // 早期採用者フラグ
            $table->boolean('is_early_adopter')->default(false)->comment('早期採用者フラグ');

            // 設定
            $table->boolean('is_private')->index()->default(false)->comment('非公開フラグ');

            // 不可視バッジフラグ
            $table->boolean('invisible_badge')->default(false)->comment('不可視バッジフラグ');

            // Laravel標準の作成・更新日時
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }
};
