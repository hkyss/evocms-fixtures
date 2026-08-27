<?php

return [
    'connection' => env('FIXTURES_CONNECTION'),

    'chunk' => (int) env('FIXTURES_CHUNK', 500),

    'panel' => [
        /** false | 'gated'. Nothing else turns it on: this endpoint writes and deletes rows. */
        'enabled' => env('FIXTURES_PANEL', false),

        /** Callable deciding who sees it. The Evolution integration supplies a manager login. */
        'gate' => null,

        /** The most documents one click may write; a bigger batch belongs on the console. */
        'max_documents' => (int) env('FIXTURES_PANEL_MAX', 20000),
    ],

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
