<?php

namespace Modules\Group\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class GroupSimpleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestMessage = $this->latestMessage;

        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'picture'       => $this->picture,
            'sender'        => $latestMessage?->sender?->full_name,
            'latestMessage' => $latestMessage?->content,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
