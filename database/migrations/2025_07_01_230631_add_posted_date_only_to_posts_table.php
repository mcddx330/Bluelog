<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('posts', function (Blueprint $table) {
            $driver = DB::connection()->getDriverName();

            switch ($driver) {
                case 'sqlite':
                    // ALTER TABLE posts ADD COLUMN posted_date_only DATE GENERATED ALWAYS AS (DATE(posted_at)) STORED;
                    $table->date('posted_date_only')->storedAs('DATE(posted_at)')->nullable()->index();
                    break;
                case 'mysql':
                case 'mariadb':
                    // ALTER TABLE posts ADD COLUMN posted_date_only DATE AS (DATE(posted_at)) PERSISTENT;
                    // PERSISTENTは物理的にデータを格納し、読み取り性能を最大化します。
                    $table->date('posted_date_only')->storedAs('DATE(posted_at)')->nullable()->index();
                    break;
                case 'pgsql':
                    // ALTER TABLE posts ADD COLUMN posted_date_only DATE GENERATED ALWAYS AS (DATE(posted_at)) STORED;
                    $table->date('posted_date_only')->storedAs('posted_at::date')->nullable()->index();
                    break;
                default:
                    // その他のデータベースの場合は、生成列を追加しないか、エラーをスローする
                    // 未対応のデータベースドライバーの場合、例外をスロー
                    throw new Exception("DBドライバーが「" . $driver . "」なため、カラム追加が失敗しました。");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('posted_date_only');
        });
    }
};
