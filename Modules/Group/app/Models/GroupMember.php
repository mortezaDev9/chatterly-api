<?php

namespace Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Group\Database\Factories\GroupMemberFactory;

class GroupMember extends Model
{
    use HasFactory;

    public const CREATED_AT = 'joined_at';
    public const UPDATED_AT = null;

    protected $guarded = [];

     protected static function newFactory(): GroupMemberFactory
     {
          return GroupMemberFactory::new();
     }
}
