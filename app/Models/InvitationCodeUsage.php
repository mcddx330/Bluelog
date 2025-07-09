<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string              $id
 * @property string              $invitation_code_id
 * @property string              $used_by_user_did
 * @property Carbon|null         $created_at
 * @property Carbon|null         $updated_at
 * @property-read InvitationCode $invitationCode
 * @property-read User           $user
 * @method static Builder<static>|InvitationCodeUsage newModelQuery()
 * @method static Builder<static>|InvitationCodeUsage newQuery()
 * @method static Builder<static>|InvitationCodeUsage query()
 * @method static Builder<static>|InvitationCodeUsage whereCreatedAt($value)
 * @method static Builder<static>|InvitationCodeUsage whereId($value)
 * @method static Builder<static>|InvitationCodeUsage whereInvitationCodeId($value)
 * @method static Builder<static>|InvitationCodeUsage whereUpdatedAt($value)
 * @method static Builder<static>|InvitationCodeUsage whereUsedByUserDid($value)
 * @mixin \Eloquent
 */
class InvitationCodeUsage extends Model {
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

    protected static function boot(): void {
        parent::boot();

        static::creating(function (Model $model) {
            $model->{'id'} = (string)Str::uuid();
        });
    }

    public function invitationCode(): BelongsTo {
        return $this->belongsTo(InvitationCode::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'used_by_user_did');
    }
}
