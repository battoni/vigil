<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dead-man's switch
    |--------------------------------------------------------------------------
    |
    | An EXTERNAL heartbeat URL (e.g. healthchecks.io / Better Stack) that the
    | scheduler pings every minute. If Vigil's own box dies, the pings stop and
    | the external service pages you — Vigil cannot alert about its own death.
    | Leave unset to disable. See PLAN.md §12.
    |
    */
    'deadman_url' => env('VIGIL_DEADMAN_URL'),
];
