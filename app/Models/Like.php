<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;


/**
 * @property string      $id
 * @property string      $did
 * @property string      $post_uri
 * @property string|null $cid
 * @property string      $post_posted_at
 * @property string      $created_by_did
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User   $user
 * @method static Builder<static>|Like newModelQuery()
 * @method static Builder<static>|Like newQuery()
 * @method static Builder<static>|Like query()
 * @method static Builder<static>|Like whereCid($value)
 * @method static Builder<static>|Like whereCreatedAt($value)
 * @method static Builder<static>|Like whereCreatedByDid($value)
 * @method static Builder<static>|Like whereDid($value)
 * @method static Builder<static>|Like whereId($value)
 * @method static Builder<static>|Like wherePostPostedAt($value)
 * @method static Builder<static>|Like wherePostUri($value)
 * @method static Builder<static>|Like whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Like extends Model {
    use HasFactory;

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
     * マスアサインメント可能な属性。
     * create() や update() メソッドで一括して割り当て可能なカラムを定義します。
     * @var array
     */
    protected $fillable = [
        'id',
        'did',
        'post_uri',
        'cid', // BlueskyのレコードCID
        'created_by_did', // いいねされた投稿の作成者のDID
        'post_posted_at', // いいねしたポストの投稿日時
        'created_at', // いいねが作成された日時
    ];

    /**
     * モデルの「起動」メソッド。
     * モデルが初期化されたときに実行されるロジックを定義します。
     * ここでは、新しいLikeモデルが作成される前にUUIDを生成します。
     */
    protected static function boot(): void {
        parent::boot();

        // 新しいレコードが作成される前にUUIDを生成し、idとして設定します。
        static::creating(function ($model) {
            $model->id = (string)\Illuminate\Support\Str::uuid();
        });
    }

    /**
     * この「いいね」が属するユーザーを取得します。
     * Like と User モデル間のリレーションシップを定義します。
     * 'did' カラムを外部キーとして使用します。
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'did', 'did');
    }
}
