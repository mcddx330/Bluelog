<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Traits\HasDatabaseSpecificQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * 
 *
 * @property string                        $id
 * @property string                        $did
 * @property string                        $uri
 * @property string                        $cid
 * @property string                        $rkey
 * @property string|null                   $text
 * @property string|null                   $reply_to
 * @property string|null                   $quote_of
 * @property bool                          $is_repost
 * @property int                           $likes_count
 * @property int                           $replies_count
 * @property int                           $reposts_count
 * @property Carbon|null                   $posted_at
 * @property Carbon|null                   $indexed_at
 * @property Carbon|null                   $created_at
 * @property Carbon|null                   $updated_at
 * @property string|null                   $reply_to_handle
 * @property string|null                   $posted_date_only
 * @property-read Collection<int, Hashtag> $hashtags
 * @property-read int|null                 $hashtags_count
 * @property-read Collection<int, Media>   $media
 * @property-read int|null                 $media_count
 * @property-read User|null                $reply_to_user
 * @property-read User                     $user
 * @method static Builder<static>|Post newModelQuery()
 * @method static Builder<static>|Post newQuery()
 * @method static Builder<static>|Post postsCountByDayOfWeek(string $did)
 * @method static Builder<static>|Post postsCountByHour(string $did)
 * @method static Builder<static>|Post query()
 * @method static Builder<static>|Post whereCid($value)
 * @method static Builder<static>|Post whereCreatedAt($value)
 * @method static Builder<static>|Post whereDid($value)
 * @method static Builder<static>|Post whereId($value)
 * @method static Builder<static>|Post whereIndexedAt($value)
 * @method static Builder<static>|Post whereIsRepost($value)
 * @method static Builder<static>|Post whereLikesCount($value)
 * @method static Builder<static>|Post wherePostedAt($value)
 * @method static Builder<static>|Post wherePostedDateOnly($value)
 * @method static Builder<static>|Post whereQuoteOf($value)
 * @method static Builder<static>|Post whereRepliesCount($value)
 * @method static Builder<static>|Post whereReplyTo($value)
 * @method static Builder<static>|Post whereReplyToHandle($value)
 * @method static Builder<static>|Post whereRepostsCount($value)
 * @method static Builder<static>|Post whereRkey($value)
 * @method static Builder<static>|Post whereText($value)
 * @method static Builder<static>|Post whereUpdatedAt($value)
 * @method static Builder<static>|Post whereUri($value)
 * @mixin \Eloquent
 */
class Post extends Model {
    use HasFactory, HasDatabaseSpecificQueries;

    /**
     * このモデルに関連付けられているテーブル名。
     * @var string
     */
    protected $table = 'posts';

    /**
     * このモデルに関連付けられているテーブルの主キー。
     * デフォルトの 'id' を使用しますが、UUIDを生成するためincrementingはfalseに設定します。
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 主キーが自動増分されるかどうかを示します。
     * UUIDを使用するため、falseに設定します。
     * @var bool
     */
    public $incrementing = false;

    /**
     * 主キーのデータ型。
     * UUIDを使用するため、'string' に設定します。
     * @var string
     */
    protected $keyType = 'string';

    /**
     * モデルの「起動」メソッド。
     */
    protected static function boot(): void {
        parent::boot();

        // 新しいレコードが作成される前にUUIDを生成し、idとして設定します。
        static::creating(function ($model) {
            $model->id = (string)\Illuminate\Support\Str::uuid();
        });
    }

    /**
     * マスアサインメント可能な属性。
     * create() や update() メソッドで一括して割り当て可能なカラムを定義します。
     * @var array
     */
    protected $fillable = [
        'uri', // Blueskyの投稿URI (at:// 形式)
        'cid', // Blueskyの投稿CID
        'did', // 投稿者のDID
        'rkey', // 投稿のユニークな識別子
        'text', // 投稿本文
        'reply_to', // リプライ先のURI
        'quote_of', // 引用元のURI
        'reply_to_handle', // リプライ先のハンドル
        'has_media', // メディアが含まれているか
        'is_repost', // リポストかどうか
        'likes_count', // いいね数
        'replies_count', // リプライ数
        'reposts_count', // リポスト数
        'posted_at', // 投稿日時
        'indexed_at', // Blueskyにインデックスされた日時
    ];

    /**
     * 属性のデータ型キャスト。
     * データベースから取得した属性値を自動的に指定されたデータ型に変換します。
     * @var array
     */
    protected $casts = [
        'indexed_at'    => 'datetime',
        'posted_at'     => 'datetime',
        'is_repost'     => 'boolean',
        'likes_count'   => 'integer',
        'replies_count' => 'integer',
        'reposts_count' => 'integer',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function media(): HasMany {
        return $this->hasMany(Media::class, 'post_cid', 'cid');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'did', 'did');
    }

    public function hashtags(): HasMany {
        return $this->hasMany(Hashtag::class);
    }

    public function reply_to_user(): BelongsTo {
        return $this->belongsTo(User::class, 'reply_to_handle', 'handle');
    }

    /**
     * Scope a query to return posts count by day of week for a given DID.
     *
     * @param Builder $query
     * @param string  $did
     */
    public function scopePostsCountByDayOfWeek(Builder $query, string $did): Builder {
        return $query->where('did', $did)
            ->selectRaw("{$this->getDayOfWeekSql()} as day_of_week, COUNT(*) as count")
            ->groupBy('day_of_week')
            ->orderBy('day_of_week', 'asc');
    }

    /**
     * Scope a query to return posts count by hour for a given DID.
     *
     * @param Builder $query
     * @param string  $did
     */
    public function scopePostsCountByHour(Builder $query, string $did): Builder {
        return $query->where('did', $did)
            ->selectRaw("{$this->getHourSql()} as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour', 'asc');
    }
}
