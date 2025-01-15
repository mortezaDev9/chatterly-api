<?php

namespace Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Group\Database\Factories\GroupFactory;
use Modules\Message\Models\Message;
use Modules\User\Models\User;

class Group extends Model
{
    use HasFactory;

    protected $guarded = [];

     protected static function newFactory(): GroupFactory
     {
          return GroupFactory::new();
     }

     public function messages(): MorphMany
     {
          return $this->morphMany(Message::class, 'messageable');
     }

     public function latestMessage(): MorphOne
     {
         return $this->morphOne(Message::class, 'messageable')->latestOfMany();
     }

     public function owner(): HasOne
     {
         return $this->hasOne(User::class, 'id', 'owner_id');
     }

     public function members(): BelongsToMany
     {
          return $this->belongsToMany(User::class, 'group_member', relatedPivotKey: 'member_id')
              ->using(GroupMember::class)
              ->as('membership')
              ->withPivot('is_admin', 'joined_at');
     }
}
