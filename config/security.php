<?php

return [
    'hybrid_crypto' => [
        'private_key_path' => env('HYBRID_RSA_PRIVATE_KEY_PATH', 'storage/app/private/keys/rsa_private.pem'),
        'private_key_passphrase' => env('HYBRID_RSA_PRIVATE_KEY_PASSPHRASE'),
        'public_key_path' => env('HYBRID_RSA_PUBLIC_KEY_PATH', 'storage/app/private/keys/rsa_public.pem'),
    ],
];
