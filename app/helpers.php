<?php

declare(strict_types = 1);

if (! function_exists('json')) {
    function json(array $data = [], int $status = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json($data, $status);
    }
}

if (! function_exists('get_enum_values')) {
    function get_enum_values(array $data): array
    {
        return array_map(fn($rule) => $rule->value, $data);
    }
}

if (! function_exists('phone_validation_rules')) {
    function phone_validation_rules(bool $unique = true): array
    {
        return [
            'required',
            'string',
            $unique ? 'unique:users,phone' : 'exists:users,phone',
            'min:10',
            'max:15',
        ];
    }
}
