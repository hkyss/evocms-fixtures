<?php

return [
    'connection' => env('FIXTURES_CONNECTION'),

    'chunk' => (int) env('FIXTURES_CHUNK', 500),

    'defaults' => [
        'documents' => 1000,
        'folders' => 0,
        'templates' => 4,
        'tmplvars' => 10,
        'values_per_document' => 4,
        'users' => 0,
        'member_groups' => 0,
        'document_groups' => 0,
    ],
];
