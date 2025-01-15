<?php

namespace Modules\Chat\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;

class ChatSimpleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->getOtherUser();

        return [
            'id'            => $this->id,
            'sender_id'     => $this->sender_id,
            'receiver_id'   => $this->receiver_id,
            'user'          => [
                'id'        => $user->id,
                'full_name' => $user->full_name,
                'avatar'    => $user->avatar,
            ],
            'latestMessage' => $this->latestMessage?->content,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
