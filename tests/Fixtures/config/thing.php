<?php

return [
    'default_status' => 'draft',
    'statuses' => ['draft', 'published'],
    'notifications' => [
        'enabled' => true,
        'channels' => ['mail', 'database'],
    ],
];
