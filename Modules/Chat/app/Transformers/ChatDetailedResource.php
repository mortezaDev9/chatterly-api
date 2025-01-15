<?php

namespace Modules\Chat\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Message\Transformers\MessageResource;
use Modules\User\Transformers\UserResource;

class ChatDetailedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'sender_id'     => $this->sender_id,
            'receiver_id'   => $this->receiver_id,
            'user'          => UserResource::make($this->whenLoaded($this->getOtherUserRole())),
            'messages'      => MessageResource::collection($this->whenLoaded('messages')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
