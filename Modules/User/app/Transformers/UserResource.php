<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'user_id'        => $this->user_id,
            'full_name'      => $this->full_name,
            'bio'            => $this->bio,
            'phone'          => $this->phone,
            'avatar'         => $this->avatar,
            'remember_token' => $this->remember_token,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
