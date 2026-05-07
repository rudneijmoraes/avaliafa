<?php

return [

    'tool_key_id' => env('LTI_TOOL_KEY_ID', 'avaliafa-lti'),

    'private_key_path' => env('LTI_PRIVATE_KEY_PATH', storage_path('oauth-private.key')),

    'public_key_path' => env('LTI_PUBLIC_KEY_PATH', storage_path('oauth-public.key')),

];
