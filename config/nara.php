<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NARA TTL Cache Directory
    |--------------------------------------------------------------------------
    |
    | Directory where NARA TTL schema files, and the parsed format table, are
    | cached.
    |
    */
    'cache_directory' => storage_path('app/nara'),

    /*
    |--------------------------------------------------------------------------
    | Cache Duration (days)
    |--------------------------------------------------------------------------
    |
    | Number of days to cache TTL files before automatic refresh.
    | Default: 28 days (4 weeks)
    |
    */
    'cache_days' => 28,

    /*
    |--------------------------------------------------------------------------
    | Download
    |--------------------------------------------------------------------------
    |
    | Set to false on servers without internet access: the assessment then
    | uses the TTL file in the cache directory, however old, and never waits
    | for a download. Copy the file there (or run nara:download-schema
    | elsewhere) to update it.
    |
    | A failed download is not retried for `retry_after_minutes`; until then
    | the old file is used, or the assessment fails at once if there is none.
    |
    */
    'download' => env('NARA_DOWNLOAD', true),

    'timeout' => 60,

    'retry_after_minutes' => 360,

    /*
    |--------------------------------------------------------------------------
    | NARA Schema URLs
    |--------------------------------------------------------------------------
    |
    | The NARA Digital Preservation Framework as TTL/RDF. The assessment reads
    | `fileformats` only; nara:download-schema fetches all of them.
    |
    */
    'schema_urls' => [
        'fileformats' => 'https://www.archives.gov/files/lod/dpframework/fileformats.ttl',
        'presaction' => 'https://www.archives.gov/files/lod/dpframework/presaction.ttl',
        'category' => 'https://www.archives.gov/files/lod/dpframework/category.ttl',
        'risk' => 'https://www.archives.gov/files/lod/dpframework/risk.ttl',
        'schema' => 'https://www.archives.gov/files/lod/dpframework/dpframeworkschema.ttl',
    ],
];
