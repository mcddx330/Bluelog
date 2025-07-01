<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Revolution\Bluesky\Agent\LegacyAgent;
use Revolution\Bluesky\BlueskyManager;
use Revolution\Bluesky\Client\SubClient\BskyClient;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\AbstractSession;
use Revolution\Bluesky\Session\LegacySession;

class BlueskyController extends Controller {
    /**
     * Blueskyセッション情報を保持するプロパティ。
     * @var AbstractSession|null
     */
    private $bluesky_session;

    /**
     * Bluesky APIのログインエンドポイントURL。
     * @var string
     */
    private $apiUrlLogin   = 'https://bsky.social/xrpc/com.atproto.server.createSession';

    /**
     * Bluesky APIのセッションリフレッシュエンドポイントURL。
     * @var string
     */
    private $apiUrlRefresh = 'https://bsky.social/xrpc/com.atproto.server.refreshSession';

    /**
     * BlueskyControllerのコンストラクタ。
     * セッションから既存のBlueskyセッション情報を取得し、プロパティに設定します。
     */
    public function __construct() {
        $this->bluesky_session = $this->bluesky_session ?? Session::get('bluesky_session');
    }

    /**
     * BlueskyセッションをLaravelのセッションに保存します。
     *
     * @param AbstractSession $session 保存するBlueskyセッションオブジェクト。
     * @return void
     */
    public function setBlueskySession(AbstractSession $session) {
        Session::put('bluesky_session', $session);
    }

    /**
     * LaravelのセッションからBlueskyセッションを取得します。
     *
     * @return AbstractSession|null 取得したBlueskyセッションオブジェクト、またはnull。
     */
    public function getBlueskySession() {
        return Session::get('bluesky_session');
    }

    /**
     * 既存のBlueskyセッションを再開します。
     * セッションが有効期限切れの場合、リフレッシュを試みます。
     *
     * @return void
     */
    public function resumeSession() {
        // 保存されているトークンを使用してBlueskyクライアントを初期化します。
        Bluesky::withToken(LegacySession::create($this->getBlueskySession()));

        // トークンが有効でない場合、リフレッシュを試みます。
        if (!Bluesky::check()) {
            Bluesky::refreshSession();
        }
    }

    /**
     * 現在のBlueskyManagerインスタンスを取得します。
     *
     * @return BlueskyManager
     */
    public function getCurrentSession(): BlueskyManager {
        return Bluesky::withToken($this->getBlueskySession());
    }

    /**
     * ログインフォームを表示します。
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function login() {
        return view('login');
    }

    /**
     * Blueskyへのログイン処理を実行します。
     * ユーザーからの識別子とパスワードを受け取り、Bluesky APIで認証を行います。
     * 認証成功後、ユーザー情報をデータベースに保存し、セッションにログイン状態を保持します。
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。
     * @return \Illuminate\Http\RedirectResponse ログイン後のリダイレクトレスポンス。
     */
    public function doLogin(Request $request) {
        // 入力データのバリデーションを行います。
        $data = $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        try {
            // Bluesky APIにログインリクエストを送信し、セッションエージェントを取得します。
            $agent = Bluesky::login($data['identifier'], $data['password'])->agent();
            // 取得したセッション情報をプロパティとLaravelセッションに保存します。
            $this->bluesky_session = $agent->session();
            $this->setBlueskySession($this->bluesky_session);

            // セッションからアクセストークン、リフレッシュトークン、DID、ハンドルを取得します。
            $access_jwt = $this->bluesky_session->get('accessJwt', null);
            $refresh_jwt = $this->bluesky_session->get('refreshJwt', null);
            $did = $this->bluesky_session->get('did', null);
            $handle = $this->bluesky_session->get('handle', null);

            // Blueskyから最新のプロフィール情報を取得します。
            $profile_response = Bluesky::getProfile($handle);
            $profile_data = json_decode($profile_response->getBody(), true);

            DB::transaction(function () use ($did, $handle, $profile_data, $access_jwt, $refresh_jwt) {
                // ユーザー情報をデータベースに保存または更新します。
                // DIDをキーとして、既存のユーザーがいれば更新、いなければ新規作成します。
                $user = User::updateOrCreate(
                    ['did' => $did],
                    [
                        'handle'            => $handle,
                        'display_name'      => $profile_data['displayName'] ?? null,
                        'description'       => $profile_data['description'] ?? null,
                        'avatar_url'        => $profile_data['avatar'] ?? null,
                        'banner_url'        => $profile_data['banner'] ?? null,
                        'followers_count'   => $profile_data['followersCount'] ?? 0,
                        'following_count'   => $profile_data['followsCount'] ?? 0,
                        'posts_count'       => $profile_data['postsCount'] ?? 0,
                        'registered_at'     => isset($profile_data['createdAt']) ? new \DateTime($profile_data['createdAt']) : null,
                        'access_jwt'        => $access_jwt,
                        'refresh_jwt'       => $refresh_jwt,
                        'last_login_at'     => now(),
                    ]
                );

                // 認証されたユーザーとしてセッションにログイン情報を保存します。
                Auth::login($user);
            });

            // ログイン成功時にstatus:aggregateコマンドを非同期実行
            dispatch(function () {
                Artisan::call('status:aggregate');
            })->onQueue('default');


            // プロフィール表示ページへリダイレクトします。
            return redirect()->route('profile.show', [
                'handle' => $handle,
            ]);
        } catch (\Exception $e) {
            // エラーが発生した場合は、デバッグ情報を出力し、エラーメッセージと共に前のページに戻ります。
            dd($e->getFile(), $e->getLine(), $e->getMessage(), $e->getTrace());

            return back()->with('error', 'エラー: ' . $e->getMessage());
        }
    }

    /**
     * 指定されたハンドルのユーザープロフィールを表示します。
     * データベースからユーザー情報と投稿データを取得し、ビューに渡します。
     *
     * @param string $handle 表示するユーザーのハンドル名。
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\Response プロフィールビューまたはエラーレスポンス。
     */
    public function showProfile(string $handle, Request $request) {
        try {
            // ハンドル名に基づいてユーザーをデータベースから検索します。
            $user = User::where('handle', $handle)->first();

            // ユーザーが見つからない場合は404エラーを返します。
            if (!($user instanceof User)) {
                return response('このユーザーはBluelogに登録していません。', 404);
            }

            // プロフィールが非公開設定の場合のチェックを行います。
            if ($user->is_private) {
                // ログインしているユーザーが、表示対象のユーザー本人でない場合は403エラーを返します。
                if (!Auth::check()
                    || (Auth::user()->did !== $user->did)
                ) {
                    return response('このプロフィールは非公開です。', 403);
                }
            }

            // データベースから取得したユーザー情報をもとに、プロフィール表示用のデータを準備します。
            $profile_data = [
                'did' => $user->did,
                'handle' => $user->handle,
                'display_name' => $user->display_name,
                'description' => $user->description,
                'avatar' => $user->avatar_url,
                'banner' => $user->banner_url,
                'followers_count' => $user->followers_count,
                'follows_count' => $user->following_count,
                'posts_count' => $user->posts->count(),
                'created_at' => $user->registered_at ? $user->registered_at->toIso8601String() : null,
            ];

            // ユーザーの投稿をデータベースから取得し、新しいものから順に20件ずつページネーションします。
            $posts = Post::where('did', $user->did)
                ->with('media') // メディア情報も一緒にロード
                ->when($request->has('date'), function ($query) use ($request) {
                    $query->whereDate('posted_at', $request->input('date'));
                })
                ->when($request->has('archive_ym'), function ($query) use ($request) {
                    $yearMonth = $request->input('archive_ym');
                    $year = substr($yearMonth, 0, 4);
                    $month = substr($yearMonth, 4, 2);
                    $query->whereYear('posted_at', $year)
                          ->whereMonth('posted_at', $month);
                })
                ->when($request->has('search_text'), function ($query) use ($request) {
                    $searchText = $request->input('search_text');
                    $query->where('text', 'LIKE', '%' . $searchText . '%');
                })
                ->when($request->has('sort'), function ($query) use ($request) {
                    $sort = $request->input('sort');
                    switch ($sort) {
                        case 'posted_at_asc': // 全て昇順
                            $query->orderBy('posted_at', 'asc');
                            break;
                        case 'posted_date_only_asc': // 全体は降順、各日は昇順
                            $query->orderBy('posted_date_only', 'desc')->orderBy('posted_at', 'asc');
                            break;
                        case 'posted_at_desc': // 全て降順
                        default:
                            $query->orderBy('posted_at', 'desc');
                            break;
                    }
                }, function ($query) {
                    // デフォルトのソート順
                    $query->orderBy('posted_at', 'desc');
                })
                ->paginate(20)->appends(request()->query());

            // メンションランキング上位10名を取得
            $top_mentions = Post::where('did', $user->did)
                ->whereNotNull('reply_to_handle')
                ->select('reply_to_handle', DB::raw('count(*) as mention_count'))
                ->groupBy('reply_to_handle')
                ->orderBy('mention_count', 'desc')
                ->orderBy('reply_to_handle', 'asc')
                ->limit(10)
                ->get();

            // ハッシュタグランキング上位10件を取得
            $top_hashtags = \App\Models\Hashtag::whereHas('post', function ($query) use ($user) {
                $query->where('did', $user->did);
            })
                ->select('tag', DB::raw('count(*) as count'))
                ->groupBy('tag')
                ->orderBy('count', 'desc')
                ->orderBy('tag', 'asc')
                ->limit(10)
                ->get();

            // 過去1年間のDailyStatデータを取得
            $dailyStats = \App\Models\DailyStat::where('did', $user->did)
                ->where('date', '>=', now()->subYear())
                ->orderBy('date', 'asc')
                ->get()
                ->mapWithKeys(function ($stat) {
                    return [$stat->date_carbon->format('Y-m-d') => $stat->posts_count];
                });

            // アーカイブリストを取得
            $archives = Post::where('did', $user->did)
                ->selectRaw('strftime("%Y%m", posted_at) as year_month')
                ->distinct()
                ->orderBy('year_month', 'desc')
                ->get()
                ->pluck('year_month')
                ->map(function ($ym) {
                    return [
                        'ym' => $ym,
                        'label' => substr($ym, 0, 4) . '年' . substr($ym, 4, 2) . '月',
                    ];
                });

            // プロフィールビューにデータを渡して表示します。
            return view('profile', [
                'did'        => $profile_data['did'],
                'handle'     => $handle,
                'profile'    => $profile_data,
                'posts'      => $posts,
                'top_mentions' => $top_mentions, // 追加
                'top_hashtags' => $top_hashtags, // 追加
                'is_fetching' => $user->isFetchingData(), // ユーザーがデータ取得中かどうかをモデルから取得します。
                'dailyStats' => $dailyStats->toJson(),
                'archives'   => $archives,
            ]);
        } catch (\Exception $e) {
            // エラーが発生した場合は、デバッグ情報を出力し、エラーメッセージと共に前のページに戻ります。
            dd($e->getFile(), $e->getLine(), $e->getMessage(), $e->getTrace());

            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * 指定されたハンドルのユーザーのメンションランキングを表示します。
     *
     * @param string $handle 表示するユーザーのハンドル名。
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\Response メンションランキングビューまたはエラーレスポンス。
     */
    public function showFriends(string $handle, Request $request) {
        try {
            $user = User::where('handle', $handle)->first();

            if (!($user instanceof User)) {
                return response('このユーザーはBluelogに登録していません。', 404);
            }

            if ($user->is_private) {
                if (!Auth::check() || (Auth::user()->did !== $user->did)) {
                    return response('このプロフィールは非公開です。', 403);
                }
            }

            $sort_by = $request->input('sort_by', 'count'); // 'count' or 'handle'
            $order = $request->input('order', 'desc'); // 'asc' or 'desc'

            $mentions = Post::where('did', $user->did)
                ->whereNotNull('reply_to_handle')
                ->select('reply_to_handle', DB::raw('count(*) as mention_count'))
                ->groupBy('reply_to_handle');

            if ($sort_by === 'handle') {
                $mentions->orderBy('reply_to_handle', $order);
            } else { // default sort by count
                $mentions->orderBy('mention_count', $order);
                $mentions->orderBy('reply_to_handle', 'asc'); // Secondary sort
            }

            $mentions = $mentions->paginate(50)->appends(request()->query());

            return view('friends', [
                'handle' => $handle,
                'mentions' => $mentions,
                'sort_by' => $sort_by,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            dd($e->getFile(), $e->getLine(), $e->getMessage(), $e->getTrace());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * ユーザーをログアウトさせ、セッションをクリアします。
     *
     * @param \Illuminate\Http\Request $request HTTPリクエストオブジェクト。
     * @return \Illuminate\Http\RedirectResponse ログアウト後のリダイレクトレスポンス。
     */
    public function doLogout(Request $request)
    {
        Auth::logout(); // ユーザーをログアウトさせます。

        $request->session()->invalidate(); // 現在のセッションを無効にします。

        $request->session()->regenerateToken(); // 新しいCSRFトークンを再生成します。

        return redirect('/'); // ログアウト後にリダイレクトするパス。
    }
}
