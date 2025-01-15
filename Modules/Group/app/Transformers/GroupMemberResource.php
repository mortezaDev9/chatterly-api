<?php

namespace Modules\Group\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'group_id'  => $this->membership->group_id,
            'member'    => [
                'id'        => $this->id,
                'full_name' => $this->full_name,
                'avatar'    => $this->avatar,
            ],
            'is_admin'  => $this->membership->is_admin,
            'joined_at' => $this->membership->joined_at,
        ];
    }
}
