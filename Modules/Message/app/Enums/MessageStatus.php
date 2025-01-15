<?php

namespace Modules\Message\Enums;

enum MessageStatus: string
{
    case SENT = 'sent';
    case READ = 'read';
    case FAILED = 'failed';
    case PENDING = 'pending';
}
