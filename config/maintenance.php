<?php

return [
    'project_source_path' => env('MAINTENANCE_PROJECT_SOURCE_PATH', base_path()),
    'update_target_path' => env('MAINTENANCE_UPDATE_TARGET_PATH', base_path()),
    'backup_storage_path' => env('MAINTENANCE_BACKUP_STORAGE_PATH', storage_path('app/backups')),
    'update_storage_path' => env('MAINTENANCE_UPDATE_STORAGE_PATH', storage_path('app/updates')),
    'allowed_update_paths' => [
        'app',
        'bootstrap',
        'config',
        'database',
        'docs',
        'public',
        'resources',
        'routes',
        'artisan',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'README.md',
    ],
];
