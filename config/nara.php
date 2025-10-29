<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NARA TTL Cache Directory
    |--------------------------------------------------------------------------
    |
    | Directory where NARA TTL schema files are cached.
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
    | NARA Schema URLs
    |--------------------------------------------------------------------------
    |
    | URLs to NARA Digital Preservation Framework TTL/RDF schema files.
    |
    */
    'schema_urls' => [
        'fileformats' => 'https://www.archives.gov/files/lod/dpframework/fileformats.ttl',
        'categories' => 'https://www.archives.gov/files/lod/dpframework/categories.ttl',
        'preservationactions' => 'https://www.archives.gov/files/lod/dpframework/preservationactions.ttl',
        'risklevel' => 'https://www.archives.gov/files/lod/dpframework/risklevel.ttl',
        'riskfactors' => 'https://www.archives.gov/files/lod/dpframework/riskfactors.ttl',
    ],
];
