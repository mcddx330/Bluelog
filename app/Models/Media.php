<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 
 *
 * @property string $id
 * @property string $post_cid
 * @property string $type
 * @property string|null $alt_text
 * @property string $size
 * @property string $mime
 * @property string $fullsize_url
 * @property string|null $thumbnail_url
 * @property int|null $aspect_ratio_width
 * @property int|null $aspect_ratio_height
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Post $post
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereAltText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereAspectRatioHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereAspectRatioWidth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFullsizeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media wherePostCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereThumbnailUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'post_cid',
        'alt_text',
        'type',
        'size',
        'mime',
        'fullsize_url',
        'thumbnail_url',
        'aspect_ratio_width',
        'aspect_ratio_height',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = (string) Str::uuid();
        });
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_cid', 'cid');
    }
}
