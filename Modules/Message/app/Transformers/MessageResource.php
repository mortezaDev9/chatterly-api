<?php

namespace Modules\Message\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'messageable_id'   => $this->messageable_id,
            'messageable_type' => $this->messageable_type,
            'sender_id'        => $this->sender_id,
            'content'          => $this->content,
            'status'           => $this->status,
            'is_edited'        => $this->is_edited,
            'sent_at'          => $this->sent_at,
        ];
    }
}
