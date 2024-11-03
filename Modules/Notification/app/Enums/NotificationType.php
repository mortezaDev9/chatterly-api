<?php

declare(strict_types=1);

namespace Modules\Notification\Enums;

enum NotificationType: string
{
    case MESSAGE = 'message';
    case MENTION = 'mention';

    public static function getValues(): array
    {
        return array_map(fn($rule) => $rule->value, self::cases());
    }
}
