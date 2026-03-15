<?php

return [

    /*
     | The API token that consumer services must send with every request.
     | Sent as: Authorization: Bearer <token>
     | Set this in your DMS .env as DMS_SERVER_TOKEN=your-strong-secret
     */
    'token' => env('DMS_SERVER_TOKEN', ''),

    /*
     | Maximum allowed upload file size in kilobytes.
     | Default: 102400 = 100 MB
     */
    'max_file_size_kb' => env('DMS_SERVER_MAX_FILE_SIZE_KB', 102400),

    /*
     | URL prefix for all DMS receiver routes.
     | Change only if /dms-disk conflicts with your existing routes.
     */
    'route_prefix' => env('DMS_SERVER_ROUTE_PREFIX', 'dms-disk'),

];
