<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Review periodicity
    |--------------------------------------------------------------------------
    |
    | How long a mitigation link goes before it must be reassessed. The next
    | review date is this many days after the link was created.
    |
    */

    'review' => [
        'interval_days' => (int) env('RME_REVIEW_INTERVAL_DAYS', 180),
    ],

];
