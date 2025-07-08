<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 *
 *
 * @property string $id
 * @property string $invitation_code_id
 * @property string $used_by_user_did
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\InvitationCode $invitationCode
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage whereInvitationCodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|InvitationCodeUsage whereUsedByUserId($value)
 * @mixin \Eloquent
 */
class InvitationCodeUsage extends Model
{
    use HasFactory;

    protected $table = 'invitation_code_usages';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invitation_code_id',
        'used_by_user_did',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->{'id'} = (string) Str::uuid();
        });
    }

    public function invitationCode(): BelongsTo
    {
        return $this->belongsTo(InvitationCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_did');
    }
}
