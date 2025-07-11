<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use App\Models\InvitationCodeUsage;
use App\Models\Post;
use App\Models\User;
use App\Traits\BuildViewBreadcrumbs;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Revolution\Bluesky\Facades\Bluesky;
use Revolution\Bluesky\Session\AbstractSession;
use Revolution\Bluesky\Session\LegacySession;
use App\Traits\PreparesProfileData;
use App\Services\SettingService;

class BlueskyController extends Controller {
    use PreparesProfileData, BuildViewBreadcrumbs;

    /**
     * Blueskyセッション情報を保持するプロパティ。
     * @var AbstractSession|null
     */
    private $bluesky_session;

    /**
     * Bluesky APIのログインエンドポイントURL。
     * @var string
     */
    private $apiUrlLogin = 'https://bsky.social/xrpc/com.atproto.server.createSession';

    /**
     * Bluesky APIのセッションリフレッシュエンドポイントURL。
     * @var string
     */
    private $apiUrlRefresh = 'https://bsky.social/xrpc/com.atproto.server.refreshSession';

    protected SettingService $settingService;

    /**
     * BlueskyControllerのコンストラクタ。
     * セッションから既存のBlueskyセッション情報を取得し、プロパティに設定します。
     */
    public function __construct(SettingService $settingService) {
        $this->bluesky_session = $this->bluesky_session ?? Session::get('bluesky_session');
        $this->settingService = $settingService;
    }

    /**
     * BlueskyセッションをLaravelのセッションに保存します。
     *
     * @param AbstractSession $session 保存するBlueskyセッションオブジェクト。
     *
     * @return void
     */
    public function setBlueskySession(AbstractSession $session) {
        Session::put('bluesky_session', $session);
    }

    /**
     * LaravelのセッションからBlueskyセッションを取得します。
     * @return AbstractSession|null 取得したBlueskyセッションオブジェクト、またはnull。
     */
    public function getBlueskySession() {
        return Session::get('bluesky_session');
    }

    /**
     * 既存のBlueskyセッションを再開します。
     * セッションが有効期限切れの場合、リフレッシュを試みます。
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
     * @return Bluesky
     */
    public function getCurrentSession() {
        return Bluesky::withToken($this->getBlueskySession());
    }

    /**
     * ログインフォームを表示します。
     * @return View
     */
    public function login() {
        $registration_mode = $this->settingService->get('registration_mode');

        return view('login', compact('registration_mode'));
    }

    /**
     * Blueskyへのログイン処理を実行します。
     * ユーザーからの識別子とパスワードを受け取り、Bluesky APIで認証を行います。
     * 認証成功後、ユーザー情報をデータベースに保存し、セッションにログイン状態を保持します。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return RedirectResponse ログイン後のリダイレクトレスポンス。
     */
    public function doLogin(Request $request) {
        // 入力データのバリデーションを行います。
        $rules = [
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ];

        $registration_mode = $this->settingService->get('registration_mode');
        $rules['invitation_code'] = 'nullable|string|size:16|exists:invitation_codes,code';
        if ($registration_mode === 'invitation_required') {
            $rules['invitation_code'] = 'required|string|size:16|exists:invitation_codes,code';
        } else if ($registration_mode === 'single_user_only') {
            $rules['invitation_code'] = 'nullable';
        }

        $data = $request->validate($rules, [
            'invitation_code.size'     => '招待コードは:size文字である必要があります。',
            'invitation_code.required' => '招待コードは必須です。',
            'invitation_code.exists'   => '無効な招待コードです。',
            'identifier.required'      => 'ユーザー名またはメールアドレスは必須です。',
            'password.required'        => 'パスワードは必須です。',
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

            // 登録モードのチェック
            if (User::all()->count() >= 1 && $registration_mode === 'single_user_only') {
                $allowed_single_user_did = $this->settingService->get('allowed_single_user_did');
                if ($did !== $allowed_single_user_did) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    return redirect(route('login'))
                        ->with('error_message', 'このBluelogインスタンスは特定のアカウントのみに制限されています。');
                }
            }

            // Blueskyから最新のプロフィール情報を取得します。
            $profile_response = Bluesky::getProfile($handle);
            $profile_data = json_decode($profile_response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            $user = DB::transaction(function () use ($did, $handle, $profile_data, $access_jwt, $refresh_jwt, $data, $registration_mode) {
                // ユーザー情報をデータベースに保存または更新します。
                // DIDをキーとして、既存のユーザーがいれば更新、いなければ新規作成します。
                $user = User::updateOrCreate(
                    ['did' => $did],
                    [
                        'handle'          => $handle,
                        'display_name'    => $profile_data['displayName'] ?? null,
                        'description'     => $profile_data['description'] ?? null,
                        'avatar_url'      => $profile_data['avatar'] ?? null,
                        'banner_url'      => $profile_data['banner'] ?? null,
                        'followers_count' => $profile_data['followersCount'] ?? 0,
                        'following_count' => $profile_data['followsCount'] ?? 0,
                        'posts_count'     => $profile_data['postsCount'] ?? 0,
                        'registered_at'   => isset($profile_data['createdAt']) ? new \DateTime($profile_data['createdAt']) : null,
                        'access_jwt'      => $access_jwt,
                        'refresh_jwt'     => $refresh_jwt,
                        'last_login_at'   => now(),
                    ]
                );

                // 最初のユーザーがログインした場合、そのユーザーのDIDをallowed_single_user_didに設定
                if ($user->wasRecentlyCreated && User::count() === 1) {
                    $this->settingService->set(
                        'allowed_single_user_did',
                        $user->did,
                        'string',
                        'シングルユーザーモードの場合に許可される唯一のユーザーのDID'
                    );
                    $user->is_admin = true;
                    $user->save();
                }

                // 新規ユーザーの場合、招待コードを処理
                if ($user->wasRecentlyCreated && $registration_mode === 'invitation_required') {
                    $invitation_code = InvitationCode::where('code', $data['invitation_code'])->first();
                    if ($invitation_code && $invitation_code->isValid()) {
                        $user->is_early_adopter = true;
                        $user->save();

                        $invitation_code->markAsUsed();

                        InvitationCodeUsage::create([
                            'invitation_code_id' => $invitation_code->id,
                            'used_by_user_did'   => $user->did,
                        ]);
                    } else {
                        // 招待コードが無効な場合はユーザーを削除し、エラーを返す
                        $user->delete();
                        throw new \Exception('無効な招待コードです。');
                    }
                }

                // 認証されたユーザーとしてセッションにログイン情報を保存します。
                Auth::login($user);

                return $user;
            });

            // 初回ログイン時にstatus:aggregateコマンドを非同期実行
            if (($user instanceof User) && !($user->posts->count() > 0)) {
                dispatch(function () {
                    Artisan::call('status:aggregate');
                })->onQueue('default');
            }

            // プロフィール表示ページへリダイレクトします。
            return redirect()->route('profile.show', [
                'handle' => $handle,
            ]);
        } catch (\Exception $e) {
            Log::error(sprintf(
                'ログイン中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return redirect(route('login'))
                ->with('error_message', 'ログイン中にエラーが発生しました。詳細についてはログを確認してください。');
        }
    }

    /**
     * ユーザーの通知を既読にします。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return RedirectResponse リダイレクトレスポンス。
     */
    public function markNotificationsAsRead(Request $request) {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications->markAsRead();
        }

        return back();
    }

    /**
     * 指定されたハンドルのユーザープロフィールを表示します。
     * データベースからユーザー情報と投稿データを取得し、ビューに渡します。
     *
     * @param string $handle 表示するユーザーのハンドル名。
     *
     * @return Application|Factory|Redirector|object|RedirectResponse|View
     */
    public function showProfile(string $handle, Request $request) {
        try {
            // ハンドル名に基づいてユーザーをデータベースから検索します。
            $user = User::where('handle', $handle)->first();

            // ユーザーが存在しない場合
            if (!($user instanceof User)) {
                return view('profile', array_merge([
                    'posts'           => collect(),
                    'last_fetched_at' => null,
                    'notifications'   => collect(),
                    'breadcrumbs'     => $this->addBreadcrumb($handle)->getBreadcrumbs(),
                    'user'            => null,
                    'profile'         => null,
                    'daily_stats'     => collect(),
                    'archives'        => collect(),
                    'top_replies'     => collect(),
                    'top_hashtags'    => collect(),
                ]));
            }

            // ユーザーが存在し、表示可能かどうかを判定
            if (!$user->canShow()) {
                return view('profile', array_merge([
                    'posts'           => collect(),
                    'last_fetched_at' => null,
                    'notifications'   => collect(),
                    'breadcrumbs'     => $this->addBreadcrumb($handle)->getBreadcrumbs(),
                    'user'            => $user, // ユーザーオブジェクトは渡す
                    'profile'         => null, // プロフィールデータは渡さない
                    'daily_stats'     => collect(),
                    'archives'        => collect(),
                    'top_replies'     => collect(),
                    'top_hashtags'    => collect(),
                ]));
            }

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

            // プロフィールビューにデータを渡して表示します。
            $notifications = collect();
            if (Auth::check()) {
                $notifications = Auth::user()->unreadNotifications;
            }


            return view('profile', array_merge([
                'posts'           => $posts,
                'last_fetched_at' => $user->last_fetched_at, // 最終更新日をビューに渡す
                'notifications'   => $notifications, // 未読通知をビューに渡す
                'breadcrumbs'     => $this->addBreadcrumb($user->handle)->getBreadcrumbs(),
            ], $this->prepareCommonProfileData($user)));
        } catch (\Exception $e) {
            Log::error(sprintf(
                'プロフィール表示中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return redirect(route('index'))->with('error', 'プロフィール表示中にエラーが発生しました。詳細についてはログを確認してください。');
        }
    }

    /**
     * 指定されたハンドルのユーザーのリプライランキングを表示します。
     *
     * @param string  $handle 表示するユーザーのハンドル名。
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return Factory|View|Application|object|RedirectResponse
     */
    public function showReplies(string $handle, Request $request) {
        try {
            $user = User::where('handle', $handle)->first();

            // ユーザーが存在しない場合
            if (!($user instanceof User)) {
                return redirect()->route('profile.show', ['handle' => $handle]);
            }

            // ユーザーが存在し、表示可能かどうかを判定
            if (!$user->canShow()) {
                return redirect()->route('profile.show', ['handle' => $handle]);
            }

            $sort_by = $request->input('sort_by', 'count'); // 'count' or 'handle'
            $order = $request->input('order', 'desc'); // 'asc' or 'desc'

            $replies = Post::where('did', $user->did)
                ->whereNotNull('reply_to_handle')
                ->select('reply_to_handle', DB::raw('count(*) as reply_count'))
                ->groupBy('reply_to_handle');
            if ($sort_by === 'handle') {
                $replies->orderBy('reply_to_handle', $order);
            } else { // default sort by count
                $replies->orderBy('replies_count', $order);
                $replies->orderBy('reply_to_handle', 'asc'); // Secondary sort
            }
            $replies = $replies->paginate(20)->appends(request()->query());

            return view('replies', array_merge([
                'breadcrumbs' => $this
                    ->addBreadcrumb($user->handle, route('profile.show', ['handle' => $user->handle]))
                    ->addBreadcrumb('リプライ一覧')
                    ->getBreadcrumbs(),
                'handle'      => $handle,
                'replies'     => $replies,
                'sort_by'     => $sort_by,
                'order'       => $order,
            ], $this->prepareCommonProfileData($user)));
        } catch (\Exception $e) {
            Log::error(sprintf(
                'リプライ表示中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return back()->with('error', 'リプライ表示中にエラーが発生しました。詳細についてはログを確認してください。');
        }
    }

    /**
     * ユーザーをログアウトさせ、セッションをクリアします。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     *
     * @return RedirectResponse ログアウト後のリダイレクトレスポンス。
     */
    public function doLogout(Request $request) {
        Auth::logout(); // ユーザーをログアウトさせます。

        $request->session()->invalidate(); // 現在のセッションを無効にします。

        $request->session()->regenerateToken(); // 新しいCSRFトークンを再生成します。

        return redirect('/'); // ログアウト後にリダイレクトするパス。
    }

    /**
     * プロフィールデータを最新の状態に更新します。
     *
     * @param string $handle 更新するユーザーのハンドル名。
     *
     * @return RedirectResponse プロフィール表示ページへのリダイレクトレスポンス。
     */
    public function updateProfileData(string $handle) {
        $user = User::where('handle', $handle)->firstOrFail();

        // status:aggregate コマンドを非同期で実行
        dispatch(function () use ($user) {
            Artisan::call('status:aggregate', [
                '--did' => $user->did,
            ]);
        })->onQueue('default');

        return redirect()->route('profile.show', ['handle' => $handle])->with('status', 'データ更新を開始しました。');
    }

    /**
     * 指定されたポストを削除します。
     *
     * @param Request $request HTTPリクエストオブジェクト。
     * @param string  $post_id 削除するポストのID。
     *
     * @return RedirectResponse リダイレクトレスポンス。
     */
    public function deletePost(Request $request, string $post_id) {
        $user = Auth::user();

        if (!$user) {
            return back()->with('error', '認証されていません。');
        }

        $post = Post::where('id', $post_id)->where('did', $user->did)->first();

        if (!$post) {
            return back()->with('error', '指定されたポストが見つからないか、削除する権限がありません。');
        }

        try {
            $post->delete();

            return back()->with('success', 'ポストが正常に削除されました。');
        } catch (\Exception $e) {
            Log::error(sprintf(
                'ポスト削除中にエラー: %s %d. %s',
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));

            return back()->with('error', 'ポストの削除中にエラーが発生しました。');
        }
    }
}
