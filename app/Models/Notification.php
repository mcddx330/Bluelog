<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;


/**
 * @property int                     $id
 * @property string                  $type
 * @property string                  $notifiable_type
 * @property int                     $notifiable_id
 * @property array<array-key, mixed> $data
 * @property Carbon|null             $read_at
 * @property Carbon|null             $created_at
 * @property Carbon|null             $updated_at
 * @property-read Model|\Eloquent    $notifiable
 * @method static Builder<static>|Notification newModelQuery()
 * @method static Builder<static>|Notification newQuery()
 * @method static Builder<static>|Notification query()
 * @method static Builder<static>|Notification whereCreatedAt($value)
 * @method static Builder<static>|Notification whereData($value)
 * @method static Builder<static>|Notification whereId($value)
 * @method static Builder<static>|Notification whereNotifiableId($value)
 * @method static Builder<static>|Notification whereNotifiableType($value)
 * @method static Builder<static>|Notification whereReadAt($value)
 * @method static Builder<static>|Notification whereType($value)
 * @method static Builder<static>|Notification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Notification extends Model {
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data'       => 'array',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
    ];

    public function notifiable(): MorphTo {
        return $this->morphTo();
    }
}
