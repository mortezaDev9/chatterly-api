<?php

namespace Modules\Message\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Message\Database\Factories\MessageFactory;

class Message extends Model
{
    use HasFactory;

    public const CREATED_AT = 'sent_at';
    public const UPDATED_AT = 'edited_at';

    protected $guarded = [];

     protected static function newFactory(): MessageFactory
     {
          return MessageFactory::new();
     }
}
