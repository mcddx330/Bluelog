## `revolution/laravel-bluesky` 利用ガイド

このドキュメントは、`revolution/laravel-bluesky` パッケージを使用して、Blueskyの主要な操作（ログイン、プロフィール取得、投稿といいねの全件取得）を行う方法を解説します。

### 1. ログイン処理

Bluesky APIを利用するには、まずユーザーの`identifier`（ハンドル名またはメールアドレス）と`password`（アプリパスワード）を使ってログインし、セッション情報を取得する必要があります。

`Bluesky::login()` メソッドは、成功すると認証情報を含む `Agent` インスタンスを返します。このインスタンスからセッション情報を取得し、Laravelのセッションに保存することで、後続のリクエストで再利用できます。

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Revolution\Bluesky\Facades\Bluesky;

public function doLogin(Request $request)
{
    $data = $request->validate([
        'identifier' => 'required|string',
        'password'   => 'required|string',
    ]);

    try {
        // ログインを実行し、Agentインスタンスを取得
        $agent = Bluesky::login($data['identifier'], $data['password'])->agent();

        // Agentからセッション情報を取得
        $bluesky_session = $agent->session();

        // Laravelのセッションに保存
        Session::put('bluesky_session', $bluesky_session);

        // ログイン後のページにリダイレクト
        return redirect()->route('posts.show');

    } catch (\Exception $e) {
        return back()->with('error', 'ログインに失敗しました: ' . $e->getMessage());
    }
}

// 後続のリクエストで認証済みクライアントを取得する例
public function getAuthenticatedClient()
{
    $session = Session::get('bluesky_session');

    if (empty($session)) {
        throw new \Exception('Not logged in.');
    }

    // セッション情報から認証済みクライアントを復元
    return Bluesky::withToken($session);
}
```

### 2. プロフィール取得

認証済みクライアントを使用して、指定したユーザーのプロフィール情報を取得します。

`getProfile()` メソッドの引数に、対象ユーザーのハンドル名またはDID (`did`) を渡します。

```php
use Revolution\Bluesky\BlueskyManager;

/**
 * @param BlueskyManager $client 認証済みクライアント
 * @param string $handleOrDid 取得したいユーザーのハンドル名またはDID
 * @return object
 */
public function fetchUserProfile(BlueskyManager $client, string $handleOrDid): object
{
    $response = $client->getProfile(actor: $handleOrDid);

    return json_decode($response->getBody());
}

// 使用例
try {
    $client = $this->getAuthenticatedClient();
    // 自分自身のプロフィールを取得する場合
    $my_did = $client->agent()->did();
    $profile = $this->fetchUserProfile($client, $my_did);

    // 特定のユーザーのプロフィールを取得する場合
    // $user_profile = $this->fetchUserProfile($client, 'bsky.app');

    // dd($profile);

} catch (\Exception $e) {
    // エラー処理
}
```

### 3. ポストの全取得

特定ユーザーの投稿（ポスト）をすべて取得するには、`getAuthorFeed()` メソッドを繰り返し呼び出す必要があります。Bluesky APIは一度に取得できる件数に制限（デフォルト/最大100件）があるため、`cursor` を使って次のページのデータを取得します。

`do-while` ループを使用し、レスポンスに `cursor` が含まれなくなるまで（＝最後のページに到達するまで）処理を繰り返します。

```php
use Revolution\Bluesky\BlueskyManager;

/**
 * @param BlueskyManager $client 認証済みクライアント
 * @param string $actorDid 取得したいユーザーのDID
 * @return array
 */
public function fetchAllPosts(BlueskyManager $client, string $actorDid): array
{
    $all_posts = [];
    $cursor = null;

    do {
        $response = $client->getAuthorFeed(
            actor: $actorDid,
            limit: 100, // 一度に取得する最大件数
            cursor: $cursor
        );

        $data = json_decode($response->getBody(), true);
        $posts = $data['feed'] ?? [];

        if (!empty($posts)) {
            $all_posts = array_merge($all_posts, $posts);
        }

        // 次のページを取得するためのカーソルを更新
        $cursor = $data['cursor'] ?? null;

    } while ($cursor !== null && !empty($posts));

    return $all_posts;
}

// 使用例
try {
    $client = $this->getAuthenticatedClient();
    $my_did = $client->agent()->did();
    $posts = $this->fetchAllPosts($client, $my_did);

    // echo "合計投稿数: " . count($posts);

} catch (\Exception $e) {
    // エラー処理
}
```

### 4. いいねの全取得

「いいね」の一覧は `app.bsky.feed.like` というコレクションにレコードとして保存されています。これを取得するには `listRecords()` メソッドを利用します。

投稿の取得と同様に、`cursor` を使ったページネーション処理が必要です。

```php
use Revolution\Bluesky\BlueskyManager;

/**
 * @param BlueskyManager $client 認証済みクライアント
 * @param string $actorDid 取得したいユーザーのDID
 * @return array
 */
public function fetchAllLikes(BlueskyManager $client, string $actorDid): array
{
    $all_likes = [];
    $cursor = null;

    do {
        $response = $client->listRecords(
            repo: $actorDid,
            collection: 'app.bsky.feed.like', // いいねのコレクションを指定
            limit: 100,
            cursor: $cursor
        );

        $data = json_decode($response->getBody(), true);
        $likes = $data['records'] ?? [];

        if (!empty($likes)) {
            $all_likes = array_merge($all_likes, $likes);
        }

        $cursor = $data['cursor'] ?? null;

    } while ($cursor !== null && !empty($likes));

    return $all_likes;
}

// 使用例
try {
    $client = $this->getAuthenticatedClient();
    $my_did = $client->agent()->did();
    $likes = $this->fetchAllLikes($client, $my_did);

    // echo "合計いいね数: " . count($likes);

} catch (\Exception $e) {
    // エラー処理
}
```
