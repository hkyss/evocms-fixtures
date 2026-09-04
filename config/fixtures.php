<?php

return [
    'connection' => env('FIXTURES_CONNECTION'),

    'chunk' => (int) env('FIXTURES_CHUNK', 500),

    'panel' => [
        /** false | 'gated'; a value meaning "on for everyone" is refused, because this endpoint writes and deletes rows. */
        'enabled' => env('FIXTURES_PANEL', false),

        /** Callable; the Evolution integration supplies a manager login. */
        'gate' => null,

        'max_documents' => (int) env('FIXTURES_PANEL_MAX', 20000),
    ],

    'defaults' => [
        'documents' => 1000,
        'folders' => 0,
        'max_depth' => 0,
        'templates' => 4,
        'tmplvars' => 10,
        'values_per_document' => 4,
        'users' => 0,
        'member_groups' => 0,
        'document_groups' => 0,
    ],
];
