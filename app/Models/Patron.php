<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property string $user_did
 * @property string|null $handle
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron whereHandle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patron whereUserDid($value)
 * @mixin \Eloquent
 */
class Patron extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_did';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_did',
        'handle',
    ];

    /**
     * Get the user that owns the Patron.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_did', 'did');
    }
}