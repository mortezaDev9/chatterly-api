<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Database\Factories\ChatFactory;
use Modules\Message\Models\Message;
use Modules\User\Models\User;

class Chat extends Model
{
    use HasFactory;

    protected $guarded = [];

     protected static function newFactory(): ChatFactory
     {
          return ChatFactory::new();
     }
     public function messages(): MorphMany
     {
          return $this->morphMany(Message::class, 'messageable');
     }

    public function latestMessage(): MorphOne
    {
        return $this->morphOne(Message::class, 'messageable')->latestOfMany();
    }

     public function sender(): BelongsTo
     {
         return $this->belongsTo(User::class, 'sender_id');
     }

     public function receiver(): BelongsTo
     {
         return $this->belongsTo(User::class, 'receiver_id');
     }

     public function getOtherUser(): User
     {
         return Auth::id() === $this->sender_id ? $this->receiver : $this->sender;
     }

    public function getOtherUserRole(): string
    {
        return Auth::id() === $this->sender_id ? 'receiver' : 'sender';
    }
}
