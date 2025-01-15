<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Message\Database\Factories\MessageFactory;
use Modules\User\Models\User;

class Message extends Model
{
    use HasFactory;

    public const CREATED_AT = 'sent_at';
    public const UPDATED_AT = null;

    protected $guarded = [];

     protected static function newFactory(): MessageFactory
     {
          return MessageFactory::new();
     }

     public function messageable(): MorphTo
     {
         return $this->morphTo();
     }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    /**
     * Readers of the message in a group
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_member', relatedPivotKey: 'member_id')
            ->using(MessageMember::class);
    }
}
