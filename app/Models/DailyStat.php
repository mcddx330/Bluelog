<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int                             $id
 * @property string                          $did
 * @property string                          $date
 * @property int                             $posts_count
 * @property int                             $likes_count
 * @property int                             $reposts_count
 * @property int                             $replies_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Carbon                     $date_carbon
 * @property-read User                       $user
 * @method static Builder<static>|DailyStat newModelQuery()
 * @method static Builder<static>|DailyStat newQuery()
 * @method static Builder<static>|DailyStat query()
 * @method static Builder<static>|DailyStat whereCreatedAt($value)
 * @method static Builder<static>|DailyStat whereDate($value)
 * @method static Builder<static>|DailyStat whereDid($value)
 * @method static Builder<static>|DailyStat whereId($value)
 * @method static Builder<static>|DailyStat whereLikesCount($value)
 * @method static Builder<static>|DailyStat wherePostsCount($value)
 * @method static Builder<static>|DailyStat whereRepliesCount($value)
 * @method static Builder<static>|DailyStat whereRepostsCount($value)
 * @method static Builder<static>|DailyStat whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DailyStat extends Model {
    use HasFactory;

    /**
     * このモデルに関連付けられているテーブル名。
     * @var string
     */
    protected $table = 'daily_stats';

    /**
     * マスアサインメント可能な属性。
     * create() や update() メソッドで一括して割り当て可能なカラムを定義します。
     * @var array
     */
    protected $fillable = [
        'did',
        'date',
        'posts_count',
        'likes_count',
        'replies_count',
        'reposts_count',
    ];

    /**
     * 属性のデータ型キャスト。
     * データベースから取得した属性値を自動的に指定されたデータ型に変換します。
     * @var array
     */
    protected $casts = [
        'posts_count'   => 'integer',
        'likes_count'   => 'integer',
        'replies_count' => 'integer',
        'reposts_count' => 'integer',
    ];

    /**
     * この日次統計情報が属するユーザーを取得します。
     * DailyStat と User モデル間のリレーションシップを定義します。
     * 'did' カラムを外部キーとして使用します。
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'did', 'did');
    }

    public function getDateCarbonAttribute(): Carbon {
        return Carbon::parse($this->date)->startOfDay();
    }
}
