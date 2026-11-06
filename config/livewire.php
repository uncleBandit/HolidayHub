<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default page layout
    |--------------------------------------------------------------------------
    |
    | Full-page components render inside the discovery layout (the HolidayHub
    | top bar, footer, mobile bottom nav and motion) unless they declare their
    | own. Provider and agent workspaces opt back into the sidebar layout.
    | Every other Livewire option keeps the package default.
    |
    */

    'layout' => 'layouts.discovery',

    /*
    |--------------------------------------------------------------------------
    | Temporary uploads
    |--------------------------------------------------------------------------
    |
    | Listings lead with video, so uploads may be up to 200 MB (PHP's
    | upload_max_filesize / post_max_size in docker/php/php.ini allow it).
    | Each form still validates what it accepts.
    |
    */

    // Package config merges one level deep, so the whole block is restated.
    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'max:204800'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma', 'webm', 'm4v', 'avif',
        ],
        'max_upload_time' => 15,
        'cleanup' => true,
    ],

];
