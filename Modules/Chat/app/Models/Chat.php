<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Chat\Database\Factories\ChatFactory;

class Chat extends Model
{
    use HasFactory;

    protected $guarded = [];

     protected static function newFactory(): ChatFactory
     {
          return ChatFactory::new();
     }
}
