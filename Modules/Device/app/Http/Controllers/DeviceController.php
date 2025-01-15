<?php

declare(strict_types = 1);

namespace Modules\Device\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Device\Transformers\DeviceResource;

class DeviceController
{
    public function index(): JsonResponse
    {
        return json([
            'data' => DeviceResource::collection(
                Auth::user()->devices()->get()
            )
        ]);
    }
}
