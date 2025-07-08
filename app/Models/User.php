<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

/**
 * 
 *
 * @property string $did
 * @property string $handle
 * @property string|null $display_name
 * @property string|null $description
 * @property string|null $avatar_url
 * @property string|null $banner_url
 * @property int $followers_count
 * @property int $following_count
 * @property-read int|null $posts_count
 * @property \Illuminate\Support\Carbon|null $registered_at
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $last_fetched_at
 * @property string|null $access_jwt
 * @property string|null $refresh_jwt
 * @property bool $is_private
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_fetching
 * @property string|null $last_synced_post_cid
 * @property string|null $last_synced_like_cid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DailyStat> $dailyStats
 * @property-read int|null $daily_stats_count
 * @property-read int $total_days_from_registered_bluesky
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Like> $likes
 * @property-read int|null $likes_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccessJwt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBannerUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFollowersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFollowingCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHandle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsFetching($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsPrivate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastFetchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastSyncedLikeCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastSyncedPostCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePostsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRefreshJwt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRegisteredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @property bool $is_early_adopter
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvitationCode> $issuedInvitationCodes
 * @property-read int|null $issued_invitation_codes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvitationCodeUsage> $usedInvitationCodes
 * @property-read int|null $used_invitation_codes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsEarlyAdopter($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable {
    use HasFactory, Notifiable;

    /**
     * このモデルに関連付けられているテーブル名。
     * @var string
     */
    protected $table = 'users';

    /**
     * このモデルに関連付けられているテーブルの主キーのカラム名。
     * BlueskyのDIDを主キーとして使用します。
     * @var string
     */
    protected $primaryKey = 'did';

    /**
     * 主キーが自動増分されるかどうかを示します。
     * DIDは自動増分されない文字列であるため、falseに設定します。
     * @var bool
     */
    public $incrementing = false;

    /**
     * 主キーのデータ型。
     * DIDは文字列であるため、'string' に設定します。
     * @var string
     */
    protected $keyType = 'string';

    /**
     * マスアサインメント可能な属性。
     * create() や update() メソッドで一括して割り当て可能なカラムを定義します。
     * @var array
     */
    protected $fillable = [
        'did', // Blueskyの分散型識別子
        'handle', // Blueskyのユーザーハンドル
        'display_name', // 表示名
        'description', // プロフィール説明
        'avatar_url', // アバター画像のURL
        'banner_url', // バナー画像のURL
        'followers_count', // フォロワー数
        'following_count', // フォロー数
        'registered_at', // Blueskyに登録した日時
        'last_login_at', // 最終ログイン日時
        'last_fetched_at', // 最終データ取得日時
        'access_jwt', // Bluesky APIへのアクセストークン
        'refresh_jwt', // Bluesky APIのリフレッシュトークン
        'is_private', // プロフィールが非公開かどうか
        'is_fetching', // データ取得中かどうかを示すフラグ
        'last_synced_post_cid',
        'last_synced_like_cid',
        'is_early_adopter',
    ];

    /**
     * 属性のデータ型キャスト。
     * データベースから取得した属性値を自動的に指定されたデータ型に変換します。
     * @var array
     */
    protected $casts = [
        'followers_count'      => 'integer',
        'following_count'      => 'integer',
        'registered_at'        => 'datetime',
        'last_login_at'        => 'datetime',
        'last_fetched_at'      => 'datetime',
        'is_private'           => 'boolean',
        'is_fetching'          => 'boolean',
        'last_synced_post_cid' => 'string',
        'last_synced_like_cid' => 'string',
        'is_early_adopter'     => 'boolean',
    ];

    public function isFetchingData(): bool {
        return $this->is_fetching;
    }

    public function markFetching(): bool {
        $this->is_fetching = true;

        return $this->save();
    }

    public function unmarkFetching(): bool {
        $this->is_fetching = false;

        return $this->save();
    }

    public function posts(): HasMany {
        return $this->hasMany(Post::class, 'did', 'did');
    }

    public function likes(): HasMany {
        return $this->hasMany(Like::class, 'did', 'did');
    }

    public function dailyStats(): HasMany {
        return $this->hasMany(DailyStat::class, 'did', 'did');
    }

    public function getTotalDaysFromRegisteredBlueskyAttribute(): int {
        return (int)$this->registered_at->diffInDays(now());
    }

    public function canShow(): bool {
        if (!$this->is_private) {
            return true; // 非公開設定でなければ常に表示可能
        }

        // 非公開設定の場合、ログインしているユーザーが本人であれば表示可能
        return Auth::check() && (Auth::user()->did === $this->did);
    }

    public function issuedInvitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class, 'issued_by_user_did');
    }

    public function usedInvitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCodeUsage::class, 'used_by_user_id');
    }

}
