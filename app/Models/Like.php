<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property string $id
 * @property string $did
 * @property string $post_uri
 * @property string $created_by_did
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $cid
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereCreatedByDid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereDid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like wherePostUri($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like whereUpdatedAt($value)
 * @property string|null $post_posted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Like wherePostPostedAt($value)
 * @mixin \Eloquent
 */
class Like extends Model
{
    use HasFactory;

    /**
     * このモデルに関連付けられているテーブルの主キー。
     * デフォルトの 'id' を使用しますが、UUIDを生成するためincrementingはfalseに設定します。
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 主キーが自動増分されるかどうかを示します。
     * UUIDを使用するため、falseに設定します。
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 主キーのデータ型。
     * UUIDを使用するため、'string' に設定します。
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * マスアサインメント可能な属性。
     *
     * create() や update() メソッドで一括して割り当て可能なカラムを定義します。
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'did',
        'post_uri',
        'cid', // BlueskyのレコードCID
        'created_by_did', // いいねされた投稿の作成者のDID
        'created_at', // いいねが作成された日時
    ];

    /**
     * モデルの「起動」メソッド。
     *
     * モデルが初期化されたときに実行されるロジックを定義します。
     * ここでは、新しいLikeモデルが作成される前にUUIDを生成します。
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // 新しいレコードが作成される前にUUIDを生成し、idとして設定します。
        static::creating(function ($model) {
            $model->id = (string) \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * この「いいね」が属するユーザーを取得します。
     *
     * Like と User モデル間のリレーションシップを定義します。
     * 'did' カラムを外部キーとして使用します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'did', 'did');
    }
}
