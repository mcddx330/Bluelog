<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 
 *
 * @property string $id
 * @property string $code
 * @property string|null $issued_by_user_did
 * @property int|null $usage_limit
 * @property int $current_usage_count
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $issuer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\InvitationCodeUsage> $usages
 * @property-read int|null $usages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereCurrentUsageCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereIssuedByUserDid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCode whereUsageLimit($value)
 * @mixin \Eloquent
 */
class InvitationCode extends Model {
    use HasFactory;

    protected $table = 'invitation_codes';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'issued_by_user_did',
        'usage_limit',
        'current_usage_count',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'usage_limit'         => 'integer',
        'current_usage_count' => 'integer',
        'expires_at'          => 'datetime',
    ];

    protected static function boot(): void {
        parent::boot();

        static::creating(function (Model $model) {
            $model->{'id'} = (string)Str::uuid();
        });
    }

    public function issuer(): BelongsTo {
        return $this->belongsTo(User::class, 'issued_by_user_did');
    }

    public function usages(): HasMany {
        return $this->hasMany(InvitationCodeUsage::class);
    }

    public function isValid(): bool {
        return $this->status === 'active'
               && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function markAsUsed(): void {
        $this->current_usage_count++;
        $this->save();
    }

    public function deactivate(): void {
        $this->status = 'inactive';
        $this->save();
    }
}
