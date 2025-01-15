<?php

namespace Modules\ContactUser\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Transformers\UserResource;

class ContactUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'contactedUser'     => [
                'id'        => $this->id,
                'full_name' => $this->full_name,
                'avatar'    => $this->avatar,
            ],
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
