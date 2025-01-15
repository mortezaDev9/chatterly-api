<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Chat\Models\Chat;
use Modules\ContactUser\Models\ContactUser;
use Modules\Group\Models\Group;
use Modules\Group\Models\GroupMember;
use Modules\Message\Models\Message;
use Modules\User\Database\Factories\UserFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $guarded = [];

    protected $hidden = ['remember_token'];

     protected static function newFactory(): UserFactory
     {
          return UserFactory::new();
     }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name . ' ' . $this->last_name),
        );
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contact_user', relatedPivotKey: 'contacted_user_id')
            ->using(ContactUser::class)
            ->withTimestamps()
            ->withPivot('id', 'first_name', 'last_name');
    }

    public function contactedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, foreignPivotKey: 'contacted_user_id')
            ->using(ContactUser::class)
            ->withTimestamps()
            ->withPivot('id', 'first_name', 'last_name');
    }

    public function sentChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'sender_id');
    }

    public function receivedChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'receiver_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_member', 'member_id')
            ->using(GroupMember::class)
            ->as('membership')
            ->withPivot('id', 'is_admin', 'joined_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Messages read by user in groups
     */
    public function readMessages(): BelongsToMany
    {
        return $this->belongsToMany(Message::class, 'message_member', 'member_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'sender_id')->latestOfMany();
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'block_user', relatedPivotKey: 'blocked_user_id')
            ->using(BlockUser::class)
            ->withPivot('blocked_at');
    }

    public function blockedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'block_user', foreignPivotKey: 'blocked_user_id')
            ->using(BlockUser::class)
            ->withPivot('blocked_at');
    }
}
