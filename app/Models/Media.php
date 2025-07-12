<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * 
 *
 * @property string      $id
 * @property string      $post_cid
 * @property string      $type
 * @property string|null $alt_text
 * @property string      $size
 * @property string      $mime
 * @property string      $fullsize_url
 * @property string|null $thumbnail_url
 * @property int|null    $aspect_ratio_width
 * @property int|null    $aspect_ratio_height
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Post   $post
 * @method static Builder<static>|Media newModelQuery()
 * @method static Builder<static>|Media newQuery()
 * @method static Builder<static>|Media query()
 * @method static Builder<static>|Media whereAltText($value)
 * @method static Builder<static>|Media whereAspectRatioHeight($value)
 * @method static Builder<static>|Media whereAspectRatioWidth($value)
 * @method static Builder<static>|Media whereCreatedAt($value)
 * @method static Builder<static>|Media whereFullsizeUrl($value)
 * @method static Builder<static>|Media whereId($value)
 * @method static Builder<static>|Media whereMime($value)
 * @method static Builder<static>|Media wherePostCid($value)
 * @method static Builder<static>|Media whereSize($value)
 * @method static Builder<static>|Media whereThumbnailUrl($value)
 * @method static Builder<static>|Media whereType($value)
 * @method static Builder<static>|Media whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Media extends Model {
    use HasFactory;

    protected $table = 'media';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot(): void {
        parent::boot();
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string)Str::uuid();
        });
    }

    public function post(): BelongsTo {
        return $this->belongsTo(Post::class, 'post_cid', 'cid');
    }
}
