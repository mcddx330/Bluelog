
# Geminiへようこそ

このドキュメントは、`gemini-cli` を使用してこのプロジェクトでの開発を支援するためのガイドです。
必ず日本語で取り扱います。

## コードルール
- 変数はスネークケース
- メソッドはキャメルケース
- 定数は全て大文字
- 値の加算、減算は、 `A += B`, `X -= Y` で表す。
- PHPの場合、メソッドの引数と返却値に、型を記す。

## プロジェクト概要

このプロジェクトは、PHPのフレームワークであるLaravelと、BlueskyのAPIを操作するためのライブラリ `revolution/laravel-bluesky` を使用しています。主な目的は、特定のBlueskyユーザーの投稿や「いいね」を収集し、Twilogのように閲覧することです。

## Gemini用ドキュメントの種別
- BLUELOG_USER_DESIGN.md: ユーザーアカウントにおける全般的な定義をまとめた書類
- BLUELOG_SYNCHRONIZE_DESIGN.md: Blueskyとのデータ同期を行う上での定義をまとめた書類

### 主要な技術スタック

*   **バックエンド:** PHP 8.2+, Laravel 11
*   **フロントエンド:** Vite, Tailwind CSS,
*   **パッケージ管理:** Composer, npm
*   **データベース:** SQLite (デフォルト)
*   **Bluesky連携:** `revolution/laravel-bluesky`, `potibm/phluesky`

## セットアップと実行

### 1. 依存関係のインストール

```bash
composer install
npm install
```

### 2. 環境変数の設定

`.env.example` をコピーして `.env` ファイルを作成します。

```bash
cp .env.example .env
```

次に、アプリケーションキーを生成し、データベースファイルを作成します。

```bash
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

### 3. 開発サーバーの起動

以下のコマンドで、PHPの組み込みサーバー、Viteの開発サーバー、キューリスナー、ログ監視を同時に起動できます。

```bash
npm run dev
```

これにより、`http://127.0.0.1:8000` でアプリケーションにアクセスできるようになります。

## Bluesky連携

このアプリケーションのコア機能は `revolution/laravel-bluesky` パッケージによって提供されます。

### 認証

`routes/web.php` を見ると、`/login` ルートでBlueskyの認証情報を入力し、それを使用して投稿や「いいね」を取得していることがわかります。

### Bluesky API認証情報の取り扱い

Bluelogは、Bluesky APIとの認証を伴う通信を行うために、`access_jwt` (アクセストークン) と `refresh_jwt` (リフレッシュトークン) を使用します。これら両方のトークンがなければ、Bluesky APIとの認証された疎通はできません。

*   **`access_jwt`**: 短期間有効なトークンで、Bluesky APIへのリクエスト認証に使用されます。
*   **`refresh_jwt`**: 長期間有効なトークンで、`access_jwt` が期限切れになった際に新しい `access_jwt` を取得（セッションのリフレッシュ）するために使用されます。

**セキュリティと永続性のため、これらの認証情報はアプリケーション内で安全に管理されます。**

1.  **`refresh_jwt` の保管**: ユーザーがBluelogにログインした際、取得した `refresh_jwt` は `users` テーブルの `refresh_jwt` カラムに**暗号化して**保存されます。これにより、ユーザーがログアウトしても、バックグラウンドでのデータ同期バッチ (`status:aggregate`) がユーザーのBlueskyデータを継続的に取得・更新できるようになります。
2.  **`access_jwt` の保管**: `access_jwt` は、ユーザーのWebセッション中にサーバーサイドのセッション（Laravelのセッション管理機能）に保存されます。これにより、ブラウザのURLやクライアントサイドのJavaScriptからトークンが露出するリスクを防ぎます。
3.  **トークンの更新**: `access_jwt` が期限切れになった場合、保存されている `refresh_jwt` を使用して新しい `access_jwt` を取得し、セッションを継続します。この処理は、ユーザーのログインセッション中、およびバッチ処理の両方で行われます。

**重要**: 認証情報をURLパラメータとして渡す方法は、セキュリティ上の重大な脆弱性（ログへの露出、傍受リスクなど）があるため、Bluelogでは採用しません。

### データ取得

*   `app/Http/Controllers/BlueskyController.php` が投稿の取得と表示を処理します。
*   `app/Http/Controllers/BlueskyLikesController.php` が「いいね」の取得と表示を処理します。

これらのコントローラー内で `revolution/laravel-bluesky` の機能が呼び出されています。

### 細々としたルール
- 一般的に表示される数値は、特別な事情がない限り、 `number_format()` にてカンマ区切りにする。

## 今後の開発方針

*   **テストの拡充:** 現在テストはほとんどありません。`tests/Feature` や `tests/Unit` ディレクトリに、Blueskyとの連携部分をモック化したテストを追加することが重要です。
*   **フロントエンドの改善:** 現在は基本的なBladeテンプレートです。Vue.jsやReactなどを導入し、よりインタラクティブなUIを構築することも考えられます。
*   **データ永続化:** 現在はリクエストごとにAPIからデータを取得していますが、取得したデータをデータベースに保存し、定期的に更新するバッチ処理を実装することで、より高速な表示とAPIリクエストの削減が可能です。
*   **エラーハンドリング:** APIリクエストの失敗など、予期せぬエラーに対するハンドリングを強化する必要があります。

## Geminiへの指示例

*   「`BlueskyController` に、取得した投稿をデータベースに保存する処理を追加して。」
*   「投稿表示画面 (`resources/views/posts.blade.php`) をTailwind CSSでもっと見やすくデザインして。」
*   「`revolution/laravel-bluesky` を使って、ユーザーのプロフィール情報を取得する機能を追加して。」
*   「`tests/Feature/BlueskyControllerTest.php` を作成し、投稿取得機能のテストを実装してください。」
