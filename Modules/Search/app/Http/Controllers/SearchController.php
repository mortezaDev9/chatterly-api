<?php

namespace Modules\Search\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Models\Chat;
use Modules\Chat\Transformers\ChatSimpleResource;
use Modules\Group\Models\Group;
use Modules\Group\Transformers\GroupSimpleResource;

class SearchController
{
    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->input('q');

        if (empty($searchTerm)) {
            return json(['data' => []]);
        }

        $chats = Chat::with(['sender', 'receiver', 'latestMessage'])
            ->where(function ($query) {
                $query->where('sender_id', Auth::id())
                    ->orWhere('receiver_id', Auth::id());
            })
            ->where(function ($query) use ($searchTerm) {
                $query->whereHas('sender', function ($subQuery) use ($searchTerm) {
                    if (DB::getDriverName() === 'sqlite') {
                        $subQuery->whereRaw("first_name || ' ' || last_name LIKE ?", ["%{$searchTerm}%"])
                            ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    } else {
                        $subQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                            ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    }
                })->orWhereHas('receiver', function ($subQuery) use ($searchTerm) {
                    if (DB::getDriverName() === 'sqlite') {
                        $subQuery->whereRaw("first_name || ' ' || last_name LIKE ?", ["%{$searchTerm}%"])
                            ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    } else {
                        $subQuery->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                            ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                            ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
                    }
                });
            })
            ->get();

        $groups = Group::with(['latestMessage'])
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->get();

        return json([
            'data' => [
                'chats'  => ChatSimpleResource::collection($chats),
                'groups' => GroupSimpleResource::collection($groups),
            ],
        ]);
    }
}
