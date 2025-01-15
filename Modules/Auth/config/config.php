<?php

return [
    'name' => 'Auth',
    'sms' => [
        'smsir' => [
            'line_number' => env('SMSIR_LINE_NUMBER'),
            'template_id' => env('SMSIR_TEMPLATE_ID'),
        ]
    ]
];
