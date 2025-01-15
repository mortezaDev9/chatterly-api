<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Message\Database\Factories\MessageMemberFactory;
use Modules\User\Models\User;

class MessageMember extends Pivot
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'member_id';
    }

     protected static function newFactory(): MessageMemberFactory
     {
          return MessageMemberFactory::new();
     }

     public function message(): BelongsTo
     {
          return $this->belongsTo(Message::class);
     }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
