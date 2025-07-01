# Bluelog データ同期・統計集計バッチ設計

## 1. 概要

このドキュメントは、Bluelogの統計機能（ステータス画面）を実現するために、バックグラウンドで定期的に実行されるデータ同期・集計処理の設計を定義する。

主な目的は、Bluesky APIから各ユーザーの最新アクティビティ（投稿、いいね等）を取得し、日別の統計データとして `daily_stats` テーブルに集計・保存することである。

## 2. コンソールコマンド仕様

- **コマンド名:** `status:aggregate`
- **ファイルパス:** `app/Console/Commands/AggregateStatusCommand.php`
- **責務:** Bluelogに登録されている全ユーザーを対象に、Bluesky APIから未取得の活動履歴を取得し、日別の統計情報としてデータベースに保存する。

## 3. 処理フロー詳細

コマンドが実行されると、以下のステップで処理が進められる。

### ステップ1: 対象ユーザーの取得

Bluelogのデータベースに登録されている全てのユーザーを `users` テーブルから取得する。

### ステップ2: ユーザーごとのループ処理

取得した全ユーザーに対して、一人ずつ以下の同期処理を実行する。

### ステップ3: Bluesky APIクライアントの準備

1.  対象ユーザーの `access_jwt` と `refresh_jwt` を `users` テーブルから取得する。
2.  アクセストークンの有効性を確認 (`check` メソッド)。
3.  もしアクセストークンが無効であれば、リフレッシュトークンを使ってトークンを更新 (`refreshSession`) し、新しいトークンを `users` テーブルに保存する。
4.  有効なトークンで認証されたAPIクライアントを準備する。

### ステップ4: 投稿フィードの取得と集計

1.  **差分取得:** `users` テーブルに保存されている `post_fetch_cursor` を利用し、`getAuthorFeed` APIを呼び出す。これにより、前回取得した時点からの新しい投稿のみを効率的に取得する。
    - **カーソルとAPIの挙動に関する補足:**
        - `getAuthorFeed` APIは、`cursor` パラメータが指定されない場合や `null` の場合、最新の投稿から順にデータを返す。
        - `status:aggregate` コマンドの投稿取得処理は `do-while` ループで実装されており、最低1回はAPI呼び出しが実行される。これは、新しい投稿がないかを確認するため、およびカーソルベースのページネーションの特性上、避けられない挙動である。
        - 既に全ての投稿が取得済みであっても、この最初のAPI呼び出しは発生し、APIが既存の投稿を返す場合がある。しかし、データベースへの保存には `Post::updateOrCreate()` が使用されているため、重複してデータが追加されることはなく、既存のデータは更新される。
2.  **データ集計:** 取得した新しい投稿を一つずつ分析し、日付ごとの活動内容をメモリ上の一時配列に集計する。
    - `createdAt` (投稿日時) をもとに、活動日を特定する。
    - `record` の内容を解析し、投稿の種類を判別する。
        - 通常の投稿 → `posts_count` をインクリメント
        - リプライ (`reply` オブジェクトが存在) → `replies_count` をインクリメント
        - リポスト (`repost` オブジェクトが存在) → `reposts_count` をインクリメント
        - メンション (`facets` 内に `mention` が存在) → `mentions_count` をインクリメント

### ステップ5: 投稿に紐づくメディア情報の取得と保存

投稿データに画像や動画などのメディア情報が含まれる場合、その詳細を抽出し、`media` テーブルに保存する。

1.  **メディア情報の検出**: 投稿データ (`$item['post']['embed']`) の `$type` を確認し、`app.bsky.embed.images#view` または `app.bsky.embed.video#view` であるかを判別する。
2.  **メディア情報の抽出**:
    *   **画像の場合 (`app.bsky.embed.images#view`)**:
        `$item['post']['embed']['images']` 配列をループし、各画像について以下の情報を抽出する。
        *   `type`: `'image'`
        *   `url`: `fullsize` URL (Bluesky CDNから直接取得可能なURL)
        *   `thumbnail_url`: `thumb` URL (Bluesky CDNから直接取得可能なサムネイルURL)
        *   `alt_text`: `alt` (代替テキスト)
        *   `aspect_ratio_width`, `aspect_ratio_height`: アスペクト比 (存在する場合)
    *   **動画の場合 (`app.bsky.embed.video#view`)**:
        動画は通常1つなので、直接以下の情報を抽出する。
        *   `type`: `'video'`
        *   `url`: `playlist` URL (Bluesky CDNから直接取得可能な動画再生リストURL)
        *   `thumbnail_url`: `thumbnail` URL (Bluesky CDNから直接取得可能なサムネイルURL)
        *   `alt_text`: `alt` (キャプション)
        *   `aspect_ratio_width`, `aspect_ratio_height`: アスペクト比 (存在する場合)
3.  **`media` テーブルへの保存**: 抽出したメディア情報を、`Post` モデルとのリレーションを通じて `media` テーブルに保存する。メディアのバイナリデータはローカルに保存せず、Bluesky CDNのURLを直接利用する。

### ステップ6: いいね履歴の取得と集計

1.  **差分取得:** `users` テーブルの `like_fetch_cursor` を利用し、`listRecords` API (`collection` は `app.bsky.feed.like`) を呼び出す。
2.  **データ集計:** 取得した新しい「いいね」レコードを一つずつ分析する。
    - `createdAt` (いいね日時) をもとに、活動日を特定する。
    - 対応する日付の `likes_count` をインクリメントする。

### ステップ7: データベースへの保存

1.  ユーザー一人分の集計が完了したら、メモリ上に保持している日別の統計データを `daily_stats` テーブルに保存する。
2.  `DailyStat::updateOrCreate()` を使用し、`['did' => $user->did, 'date' => $date]` を複合キーとして、データの登録・更新を行う。
    - レコードが新規作成される場合は、集計したカウントをそのまま保存する。
    - レコードが既に存在する場合は、既存のカウント値に今回集計した値を加算して更新する。

## 4. 自動実行（スケジューリング）

作成した `status:aggregate` コマンドは、`app/Console/Kernel.php` の `schedule` メソッドに登録し、cron（クーロン）によって定期的に自動実行されるように設定する。

- **実行頻度（案）:** 1時間ごと (`hourly()`) または 1日1回 (`daily()`)。アプリケーションの運用方針に応じて決定する。

## 5. 関連データモデル

- `app/Models/User.php`
- `app/Models/DailyStat.php`
- `app/Models/Media.php` (新規追加)
- `database/migrations/*_create_media_table.php` (新規追加)
