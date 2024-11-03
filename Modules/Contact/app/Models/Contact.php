<?php

namespace Modules\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Modules\Contact\Database\Factories\ContactFactory;

class Contact extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = [];

     protected static function newFactory(): ContactFactory
     {
          return ContactFactory::new();
     }
}
