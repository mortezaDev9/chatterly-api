<?php

namespace Modules\Group\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Modules\Message\Transformers\MessageResource;
use Modules\User\Transformers\UserResource;

class GroupDetailedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'group_id'    => $this->group_id,
            'owner_id'    => $this->owner_id,
            'name'        => $this->name,
            'picture'     => $this->picture,
            'description' => $this->description,
            'members'     => UserResource::collection($this->whenLoaded('members')),
            'messages'    => MessageResource::collection($this->whenLoaded('messages')),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
