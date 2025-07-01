
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
- **Job (`FetchProfile`, `FetchPosts`):** Bluesky APIとの通信、データ取得、DBへの保存という重い処理を担当。
- **Command (`SyncUserData`):** 全ユーザーのデータ同期ジョブを定期的に投入する役割。
- **Scheduler (`app/Console/Kernel.php`):** `SyncUserData` コマンドの実行スケジュールを定義。
- **Model (`User`, `Post`):** データベースとのやり取りを担当。

## 4. 詳細設計

### 4.1. データベースの変更

`users` テーブルに、投稿の差分取得を管理するためのカラムを追加します。

**`database/migrations/xxxx_xx_xx_xxxxxx_add_cursor_to_users_table.php`**

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('post_fetch_cursor')->nullable()->after('refresh_jwt')->comment('投稿取得用のカーソル');
    $table->string('like_fetch_cursor')->nullable()->after('post_fetch_cursor')->comment('いいね取得用のカーソル');
});
```

### 4.2. データ同期処理の実装

#### a. 投稿取得ジョブ (`app/Jobs/FetchPosts.php`)

- `User` モデルをコンストラクタで受け取ります。
- `revolution/laravel-bluesky` を使用して `accessJwt` をリフレッシュします。
- `users` テーブルの `post_fetch_cursor` を利用して `getAuthorFeed` を呼び出します。
- 取得した投稿を `posts` テーブルに `updateOrCreate` で保存します。（`uri` をキーにする）
- APIレスポンスに含まれる新しい `cursor` を `users` テーブルの `post_fetch_cursor` に保存します。
- APIの100件制限を超える場合は、`cursor` を使いながら複数回リクエストを送信します。

#### b. 定期実行コマンド (`app/Console/Commands/SyncUserData.php`)

- `User::all()` などで同期対象の全ユーザーを取得します。
- 各ユーザーに対して `FetchPosts::dispatch($user)` のようにジョブを投入します。
- （将来的にはプロフィール更新やいいね取得のジョブもここから投入します）

#### c. スケジューラへの登録 (`app/Console/Kernel.php`)

```php
protected function schedule(Schedule $schedule): void
{
    // 毎時0分にユーザーデータ同期コマンドを実行
    $schedule->command('app:sync-user-data')->hourly();
}
```

#### d. ログイン処理の変更 (`app/Http/Controllers/BlueskyController.php`)

`doLogin` メソッド内で、ユーザー情報をDBに保存した直後に、初回のデータ取得ジョブを投入します。

```php
// ... ログイン処理
$user = User::updateOrCreate(...);

// 初回データ取得ジョブを投入
\App\Jobs\FetchPosts::dispatch($user);
// \App\Jobs\FetchLikes::dispatch($user); // 必要であれば

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
2.  `php artisan make:job FetchPosts` でジョブクラスを作成し、投稿取得ロジックを実装する。
3.  `php artisan make:command SyncUserData` でコマンドクラスを作成し、全ユーザーに対してジョブを投入するロジックを実装する。
4.  `app/Console/Kernel.php` に作成したコマンドをスケジュール登録する。
5.  `BlueskyController@doLogin` に、初回ジョブ投入の処理を追加する。
6.  キューワーカー (`php artisan queue:work`) を起動して、ジョブが正常に処理されることを確認する。
7.  `BlueskyController@showProfile` を、データベースからデータを表示するようリファクタリングする。

以上が、提案するデータ同期の設計です。この設計により、効率的でスケーラブルなデータ収集基盤を構築できます。
