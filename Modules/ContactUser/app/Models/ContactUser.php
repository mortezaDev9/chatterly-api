<?php

namespace Modules\ContactUser\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\ContactUser\Database\Factories\ContactUserFactory;
use Modules\User\Models\User;

class ContactUser extends Pivot
{
    use HasFactory;

    public $incrementing = true;

    protected $guarded = [];

    public function getRouteKeyName(): string
    {
        return 'contacted_user_id';
    }

     protected static function newFactory(): ContactUserFactory
     {
          return ContactUserFactory::new();
     }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name . ' ' . $this->last_name),
        );
    }

     public function user(): BelongsTo
     {
         return $this->belongsTo(User::class);
     }

     public function contactedUser(): BelongsTo
     {
         return $this->belongsTo(User::class, 'contacted_user_id');
     }
}
