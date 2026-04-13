<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Investment Request Authorizer
    |--------------------------------------------------------------------------
    |
    | Email of the single authorizer for all investment requests.
    | This user will receive approval notifications and can approve/reject.
    |
    */

    'authorizer_email' => env('INVESTMENT_AUTHORIZER_EMAIL', 'victor.setien@grupocosteno.com'),

];
