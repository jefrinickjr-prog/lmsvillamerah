<?php

return [
    'enabled' => (bool) env('GOOGLE_DRIVE_ENABLED', false),
    'shared_drive_id' => env('GOOGLE_DRIVE_SHARED_DRIVE_ID'),
    'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),
    'service_account_path' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_PATH', storage_path('app/private/google-workspace-service-account.json')),
    'delete_local_after_sync' => (bool) env('GOOGLE_DRIVE_DELETE_LOCAL_AFTER_SYNC', false),
];
