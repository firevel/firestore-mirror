<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Queue Data Mirroring
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that mirror your data
    | to Firestore are queued. When this is set to "true" then
    | all automatic data mirroring will get queued for better performance.
    |
    */

    'queue' => env('FIRESTORE_MIRROR_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into Firestore. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'mirror' => 500,
        'delete' => 500,
    ],

];
