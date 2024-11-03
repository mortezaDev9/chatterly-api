<?php

namespace Modules\Notification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Notification\Database\Factories\NotificationFactory;

class Notification extends Model
{
    use HasFactory;

    public const CREATED_AT = 'sent_at';
    public const UPDATED_AT = null;

    protected $guarded = [];

     protected static function newFactory(): NotificationFactory
     {
          return NotificationFactory::new();
     }
}
