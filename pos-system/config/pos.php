<?php

return [
    // First admin login created by the seeder (password random if empty).
    'admin_email' => env('POS_ADMIN_EMAIL', 'admin@pos.local'),
    'admin_password' => env('POS_ADMIN_PASSWORD'),

    // Shared secret for POST /_deploy (called by GitHub Actions after each FTP upload).
    // Empty = the deploy hook is disabled.
    'deploy_token' => env('POS_DEPLOY_TOKEN'),
];
