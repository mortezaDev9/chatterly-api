<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Message\Database\Factories\MessageStatusFactory;

class MessageStatus extends Model
{
    use HasFactory;

    public const CREATED_AT = 'read_at';
    public const UPDATED_AT  = null;

    protected $guarded = [];

     protected static function newFactory(): MessageStatusFactory
     {
          return MessageStatusFactory::new();
     }
}
