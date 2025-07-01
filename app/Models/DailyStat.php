<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $did
 * @property \Illuminate\Support\Carbon $date
 * @property int $posts_count
 * @property int $likes_count
 * @property int $replies_count
 * @property int $reposts_count
 * @property int $mentions_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereDid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereLikesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereMentionsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat wherePostsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereRepliesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereRepostsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyStat whereUpdatedAt($value)
 * @property-read Carbon $date_carbon
 * @mixin \Eloquent
 */
class DailyStat extends Model
{
    use HasFactory;

    /**
     * このモデルに関連付けられているテーブル名。
     *
     * @var string
     */
    protected $table = 'daily_stats';

    /**
     * マスアサインメント可能な属性。
     *
     * create() や update() メソッドで一括して割り当て可能なカラムを定義します。
     *
     * @var array
     */
    protected $fillable = [
        'did',
        'date',
        'posts_count',
        'likes_count',
        'replies_count',
        'reposts_count',
        'mentions_count',
    ];

    /**
     * 属性のデータ型キャスト。
     *
     * データベースから取得した属性値を自動的に指定されたデータ型に変換します。
     *
     * @var array
     */
    protected $casts = [
        'posts_count' => 'integer',
        'likes_count' => 'integer',
        'replies_count' => 'integer',
        'reposts_count' => 'integer',
        'mentions_count' => 'integer',
    ];

    /**
     * この日次統計情報が属するユーザーを取得します。
     *
     * DailyStat と User モデル間のリレーションシップを定義します。
     * 'did' カラムを外部キーとして使用します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'did', 'did');
    }

    public function getDateCarbonAttribute(): Carbon {
        return Carbon::parse($this->date)->startOfDay();
    }
}
