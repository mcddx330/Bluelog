<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string      $id
 * @property string      $post_id
 * @property string      $tag
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Post   $post
 * @method static Builder<static>|Hashtag newModelQuery()
 * @method static Builder<static>|Hashtag newQuery()
 * @method static Builder<static>|Hashtag query()
 * @method static Builder<static>|Hashtag whereCreatedAt($value)
 * @method static Builder<static>|Hashtag whereId($value)
 * @method static Builder<static>|Hashtag wherePostId($value)
 * @method static Builder<static>|Hashtag whereTag($value)
 * @method static Builder<static>|Hashtag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Hashtag extends Model {
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'tag',
    ];

    /**
     * Get the post that owns the hashtag.
     */
    public function post(): BelongsTo {
        return $this->belongsTo(Post::class);
    }
}
