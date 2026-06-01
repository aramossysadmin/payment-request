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

    'authorizer_email' => env('INVESTMENT_AUTHORIZER_EMAIL', 'moises.monrroy@grupocosteno.com'),

    /*
    |--------------------------------------------------------------------------
    | Require Authorization
    |--------------------------------------------------------------------------
    |
    | When true (default), new Investment Requests go through the normal
    | approval flow: pending_department state + email to authorizer with token.
    | When false, new requests are auto-approved (Completed) on creation and
    | no email is sent. Temporary bypass while the pool authorization pattern
    | is implemented.
    |
    */

    'require_authorization' => env('INVESTMENT_REQUEST_REQUIRE_AUTHORIZATION', true),

];
