
# Blueskyログシステム 設計案

## 1. はじめに

このドキュメントは、Twilogのクローンとして開発するBlueskyログシステムの、ログイン後のデータ同期処理に関する設計案を定義するものです。

現在の実装では、ユーザーハンドルを指定して公開プロフィールを閲覧できます。次のステップとして、ユーザー認証後にそのユーザーの投稿や「いいね」を継続的に収集し、データベースに保存するための堅牢な仕組みを構築します。

## 2. 基本方針

ユーザーからの提案通り、以下の基本方針で設計します。

- **非同期処理:** ユーザーの投稿やプロフィール情報の取得は、APIとの通信が発生し時間がかかるため、Laravelの**キュー（Queue）**を利用したバックグラウンドジョブとして実行します。これにより、ユーザーは待ち時間なくアプリケーションを操作できます。
- **トークンの安全な管理:** ログイン時に取得した `accessJwt` と `refreshJwt` は `users` テーブルに保存します。バックグラウンドジョブは、このトークン（特に `refreshJwt`）を使用してAPIにアクセスします。
- **差分更新:** Bluesky APIの `getAuthorFeed` が提供する `cursor` パラメータを利用し、前回の取得以降の新しい投稿のみを取得します。これにより、APIリクエスト数を最小限に抑え、効率的なデータ同期を実現します。
- **定期的・自動的な同期:** Laravelの**タスクスケジューラ**を使用し、定期的に全ユーザーのデータを自動で更新するバッチ処理を実装します。

## 3. 全体アーキテクチャ

### 3.1. データフロー

```
                                 +-------------------------+
                                 |     Bluesky API         |
                                 +-------------------------+
                                     ^   | (3) API Request
                                     |   | (Profile, Posts)
+----------+   (1) Login   +-------------------------+   (4) Dispatch Job   +----------------+
|          |-------------->|                         |--------------------->|                |
|  User    |               |   Laravel Application   |                      |  Queue (Redis, |
|          |<--------------|   (Controllers, etc.)   |<---------------------|  Database, etc)|
+----------+  (2) Redirect |                         |                      |                |
             & Show Profile|                         |   (5) Process Job    +----------------+
                           +-------------------------+                      ^
                                 |           ^                              |
                                 |           | (6) Fetch Data & Save        | (7) Execute
                                 v           |                              | Command
                           +-------------------------+               +----------------+
                           |      Database           |               | Task Scheduler |
                           | (users, posts, etc.)    |               | (Kernel.php)   |
                           +-------------------------+               +----------------+
```

1.  **ログイン:** ユーザーがID/パスワードを入力し、`BlueskyController@doLogin` が実行されます。
2.  **トークン保存とリダイレクト:** `users` テーブルにユーザー情報とトークンが保存され、プロフィールページにリダイレクトされます。
3.  **初回ジョブ投入:** ログイン成功と同時に、そのユーザーのデータを取得するためのジョブがキューに投入（Dispatch）されます。
4.  **ジョブ処理:** バックグラウンドのキューワーカーがジョブを受け取り、Bluesky APIにリクエストを送信します。
5.  **データ保存:** ジョブは取得したプロフィールや投稿を `users` テーブルや `posts` テーブルに保存します。
6.  **定期実行:** Laravelのタスクスケジューラが、定義された間隔（例: 15分ごと）で全ユーザーのデータ同期コマンドを実行します。
7.  **全ユーザージョブ投入:** 同期コマンドは、アクティブな全ユーザーのデータ取得ジョブをキューに投入します。

### 3.2. 主要コンポーネント

- **Controller (`BlueskyController`):** ユーザー認証、初回ジョブの投入、DBに保存されたデータの表示を担当。
- **Job (`AggregateStatusCommand`):** Bluesky APIとの通信、データ取得、DBへの保存という重い処理を担当。
- **Command (`AggregateStatusCommand`):** 全ユーザーのデータ同期ジョブを定期的に投入する役割。
- **Scheduler (`app/Console/Kernel.php`):** `AggregateStatusCommand` コマンドの実行スケジュールを定義。
- **Model (`User`, `Post`):** データベースとのやり取りを担当。

## 4. 詳細設計

### 4.1. データベースの変更

`users` テーブルに、投稿の差分取得を管理するためのカラムを追加します。

**`database/migrations/xxxx_xx_xx_xxxxxx_add_cursor_to_users_table.php`**

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('last_synced_post_cid')->nullable()->after('refresh_jwt')->comment('最後に同期した投稿のCID');
    $table->string('last_synced_like_cid')->nullable()->after('last_synced_post_cid')->comment('最後に同期したいのCID');
});
```

このマイグレーションにより `last_synced_post_cid` と `last_synced_like_cid` の二つのカラムが追加されました。これらは `AggregateStatusCommand` において、Bluesky API との同期処理で取得済みの位置を記録するために使用されています。差分取得時にはこれらの値を基準とし、効率的なデータ更新を実現しています。

### 4.2. データ同期処理の実装

#### a. データ集計コマンド (`app/Console/Commands/AggregateStatusCommand.php`)

- `User` モデルを元に、Bluesky APIからプロフィール、投稿、いいねなどのデータを取得します。
- 取得したデータは、`users`, `posts`, `likes`, `media`, `hashtags`, `daily_stats` テーブルに保存されます。
- `is_fetching` フラグを使用して、データ取得中のユーザーをマークします。
- Bluesky APIの `cursor` を利用し、効率的な差分更新を行います。
- `accessJwt` が期限切れの場合、`refreshJwt` を使用して自動的に更新されます。

#### b. 定期実行コマンド (`app/Console/Commands/AggregateStatusCommand.php`)

- `php artisan status:aggregate` コマンドを実行することで、全ユーザーのデータ同期が開始されます。
- このコマンドは、`is_fetching` フラグが `false` のユーザー、または `is_fetching` が `null` のユーザーを対象にデータを取得します。
- 特定のユーザーのデータを取得する場合は、`--did` オプションを使用します。

#### c. スケジューラへの登録 (`app/Console/Kernel.php`)

```php
protected function schedule(Schedule $schedule): void
{
    // 毎時0分にユーザーデータ集計コマンドを実行
    $schedule->command('status:aggregate')->hourly();
}
```

#### d. ログイン処理の変更 (`app/Http/Controllers/BlueskyController.php`)

`doLogin` メソッド内で、ユーザー情報をDBに保存した直後に、`status:aggregate` コマンドを非同期で実行します。

```php
// ... ログイン処理
$user = User::updateOrCreate(...);

// 初回データ取得コマンドを非同期実行
dispatch(function () use ($user) {
    Artisan::call('status:aggregate', [
        '--did' => $user->did,
    ]);
})->onQueue('default');

Auth::login($user);

return redirect()->route('profile.show', ['handle' => $handle]);
```

### 4.3. 表示処理の変更

`BlueskyController@showProfile` は、現状では毎回APIからデータを取得していますが、将来的にはデータベースからデータを取得して表示するように変更します。これにより、表示速度が向上し、API制限の影響を受けにくくなります。

```php
public function showProfile(string $handle)
{
    // ハンドル名からユーザーをDBで検索
    $user = User::where('handle', $handle)->firstOrFail();
    // ユーザーに紐づく投稿をDBから取得
    $posts = Post::where('did', $user->did)->orderBy('indexed_at', 'desc')->paginate(50);

    return view('profile', [
        'profile' => $user, // Userモデルをそのまま渡す
        'posts'   => $posts,
    ]);
}
```

## 5. 実装ステップ案

1.  `users` テーブルにカーソル用カラムを追加するマイグレーションを作成・実行する。
2.  `app/Console/Commands/AggregateStatusCommand.php` にデータ集計ロジックを実装する。
3.  `app/Console/Kernel.php` に `status:aggregate` コマンドをスケジュール登録する。
4.  `BlueskyController@doLogin` に、初回データ取得コマンドの非同期実行処理を追加する。
5.  キューワーカー (`php artisan queue:work`) を起動して、コマンドが正常に処理されることを確認する。
6.  `BlueskyController@showProfile` を、データベースからデータを表示するようリファクタリングする。

以上が、提案するデータ同期の設計です。この設計により、効率的でスケーラブルなデータ収集基盤を構築できます。
