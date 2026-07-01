<?php

return [

    'tenant_id' => env('MICROSOFT_TENANT_ID', env('GRAPH_TENANT_ID')),

    'client_id' => env('MICROSOFT_CLIENT_ID'),

    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),

    'redirect' => env('MICROSOFT_REDIRECT_URI', env('APP_URL').'/auth/microsoft/callback'),

    'scopes' => ['openid', 'profile', 'email', 'User.Read'],

];
