<?php

namespace Modules\Device\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'device_id'  => $this->device_id,
            'browser'    => $this->browser,
            'ip_address' => $this->ip_address,
            'logged_at'  => $this->logged_at,
        ];
    }
}
