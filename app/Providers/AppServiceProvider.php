<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションのサービスを登録します。
     *
     * ここでは、サービスコンテナにサービスバインディングを登録します。
     * 主に、アプリケーションの起動時に必要な依存関係の解決や、
     * サービスプロバイダの登録などを行います。
     * このメソッド内でサービスをロードしたり、イベントリスナーを登録したりするべきではありません。
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * アプリケーションのサービスをブートストラップします。
     *
     * ここでは、アプリケーションの起動後に実行されるべき処理を記述します。
     * サービスプロバイダによって提供されるサービスがすべて登録された後で実行されるため、
     * 他のサービスに依存する処理（例: ルートの登録、イベントリスナーの登録、ビューコンポーザーの登録など）
     * を行うのに適しています。
     *
     * @return void
     */
    public function boot(): void
    {
        Blade::directive('renderBlueskyText', function ($expression) {
            return "<?php echo \App\Providers\AppServiceProvider::renderBlueskyText($expression); ?>";
        });
    }

    public static function renderBlueskyText(string $text): string
    {
        // ハッシュタグを検出してリンクに変換
        $text = preg_replace_callback(
            '/(#)([\p{L}\p{N}_]+)/u',
            function ($matches) {
                $tag = $matches[2];
                $encodedTag = urlencode($tag);
                return '<a href="https://bsky.app/search?q=%23' . $encodedTag . '" target="_blank" class="text-blue-500 hover:underline">#' . $tag . '</a>';
            },
            $text
        );

        // メンションを検出してリンクに変換
        $text = preg_replace_callback(
            '/(@)([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/',
            function ($matches) {
                $handle = $matches[2];
                return '<a href="https://bsky.app/profile/' . $handle . '" target="_blank" class="text-blue-500 hover:underline">@' . $handle . '</a>';
            },
            $text
        );

        return nl2br($text);
    }
}
