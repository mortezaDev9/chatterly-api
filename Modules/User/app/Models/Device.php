<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Device\Database\Factories\DeviceFactory;

class Device extends Model
{
    use HasFactory;

    public const CREATED_AT = 'logged_at';
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function newFactory(): DeviceFactory
    {
        return DeviceFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
