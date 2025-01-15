<?php

namespace Modules\Group\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Group\Database\Factories\GroupMemberFactory;
use Modules\User\Models\User;

class GroupMember extends Pivot
{
    use HasFactory;

    public const CREATED_AT = 'joined_at';
    public const UPDATED_AT = null;

    public $incrementing = true;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'member_id';
    }

     protected static function newFactory(): GroupMemberFactory
     {
          return GroupMemberFactory::new();
     }

     public function group(): BelongsTo
     {
          return $this->belongsTo(Group::class);
     }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
