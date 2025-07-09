<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $user_did
 * @property string|null $handle
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User   $user
 * @method static Builder<static>|Patron newModelQuery()
 * @method static Builder<static>|Patron newQuery()
 * @method static Builder<static>|Patron query()
 * @method static Builder<static>|Patron whereCreatedAt($value)
 * @method static Builder<static>|Patron whereHandle($value)
 * @method static Builder<static>|Patron whereId($value)
 * @method static Builder<static>|Patron whereUpdatedAt($value)
 * @method static Builder<static>|Patron whereUserDid($value)
 * @mixin \Eloquent
 */
class Patron extends Model {
    use HasFactory;

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = 'user_did';

    /**
     * Indicates if the IDs are auto-incrementing.
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     * @var array<int, string>
     */
    protected $fillable = [
        'user_did',
        'handle',
    ];

    /**
     * Get the user that owns the Patron.
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'user_did', 'did');
    }
}
