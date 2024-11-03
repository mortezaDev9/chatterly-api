<?php

namespace Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Group\Database\Factories\GroupFactory;

class Group extends Model
{
    use HasFactory;

    protected $guarded = [];

     protected static function newFactory(): GroupFactory
     {
          return GroupFactory::new();
     }
}
