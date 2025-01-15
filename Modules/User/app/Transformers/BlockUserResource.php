<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'full_name'  => $this->full_name,
            'phone'      => $this->phone,
            'avatar'     => $this->avatar,
            'blocked_at' => $this->pivot->blocked_at,
        ];
    }
}
