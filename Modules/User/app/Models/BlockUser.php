<?php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\User\Database\Factories\BlockUserFactory;

class BlockUser extends Pivot
{
    use HasFactory;

    public const CREATED_AT = 'blocked_at';
    public const UPDATED_AT = null;

    public $incrementing = true;

    protected $guarded = [];

    protected static function newFactory(): BlockUserFactory
    {
        return BlockUserFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }
}
