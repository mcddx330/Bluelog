# BlueLog

BlueLog は、Bluesky ユーザーの投稿や「いいね」を収集し、時系列に閲覧できるようにする Laravel 製アプリケーションです。自分の環境にインストールしてセルフホスティングすることを想定しています。

## 主な機能

- Bluesky アカウントでログインし、投稿や「いいね」をデータベースに保存
- 投稿検索、日付アーカイブ、並び替え表示
- リプライランキング、ハッシュタグランキングの表示
- 投稿データの CSV エクスポート
- アカウント削除時は関連データをすべて自動削除
- 招待コード制によるユーザー登録制御
- 管理者向け設定画面（全体設定、招待コード管理 など）

## 動作環境

- PHP 8.2 以上
- Node.js 18 以上
- SQLite3 (開発用デフォルト)

## Docker 環境

`docker-compose.yml` を用意しており、PHP 8.4 と MariaDB (Mroonga プラグイン入り) を使った環境を簡単に立ち上げられます。

```bash
cp .env.example .env
docker compose build
docker compose up -d
```

Web サーバーは `http://localhost:8000` で起動します。初回起動後、以下でマイグレーションを実行してください。

```bash
docker compose exec app php artisan migrate
```

## セットアップ手順

1. リポジトリをクローンし依存パッケージをインストールします。

   ```bash
   composer install
   npm install
   ```

2. `.env` を準備します。Laravel の `.env.example` を参考に必要な環境変数を設定してください。主に以下を設定します。

   - `APP_KEY`： `php artisan key:generate` で生成
   - `DB_CONNECTION=sqlite`
   - `DB_DATABASE=database/database.sqlite`
   - Bluesky 用の `BLUESKY_IDENTIFIER`, `BLUESKY_PASSWORD` など

3. データベースファイルを作成しマイグレーションを実行します。

   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

4. 開発サーバーを起動します。

   ```bash
   npm run dev
   ```

   `npm run dev` には Laravel 開発サーバー、キューワーカー、ログ監視、Vite がまとめて起動するスクリプトが登録されています。

## 使い方

1. Web ブラウザで `http://localhost:8000` にアクセスします。
2. Bluesky のハンドルとアプリパスワードでログインします。
3. 初回ログイン時に投稿と「いいね」の取得ジョブが自動実行されます。
4. プロフィールページでは検索・アーカイブ・並び替えが可能です。設定画面からデータ再取得やエクスポート、招待コード管理などを行えます。

## データ同期

Bluesky からのデータ取得は `bluelog:aggregate` コマンドで行います。キューワーカーが実行中であれば自動的にバックグラウンド処理されます。定期的な同期を行う場合は cron などで以下を設定してください。

```bash
php artisan schedule:run
```

`routes/console.php` では毎時 `bluelog:aggregate` が実行されるように登録されています。

Docker 環境で定期実行させる場合は `scheduler` サービスが自動で `php artisan schedule:work` を実行します。コンテナを起動しておけばバッチ処理も継続して実行されます。

## ライセンス

本ソフトウェアは MIT ライセンスで配布されます。
