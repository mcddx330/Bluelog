<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property string $id
 * @property string $post_id
 * @property string $tag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Post $post
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Hashtag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Hashtag extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'tag',
    ];

    /**
     * Get the post that owns the hashtag.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}