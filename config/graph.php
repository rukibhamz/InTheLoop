<?php

return [

    'tenant_id' => env('GRAPH_TENANT_ID'),

    'client_id' => env('GRAPH_CLIENT_ID'),

    'client_secret' => env('GRAPH_CLIENT_SECRET'),

    'default_sender_mailbox' => env('GRAPH_DEFAULT_SENDER_MAILBOX'),

    'monitored_mailboxes' => array_values(array_filter(array_map(
        fn (string $mailbox) => trim($mailbox),
        explode(',', env('GRAPH_MONITORED_MAILBOXES', ''))
    ))),

    'announcement_mailboxes' => array_values(array_filter(array_map(
        fn (string $mailbox) => trim($mailbox),
        explode(',', env('GRAPH_ANNOUNCEMENT_MAILBOXES', ''))
    ))),

    'token_cache_key' => 'graph_app_access_token',

    'base_url' => 'https://graph.microsoft.com/v1.0',

];
