<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verification Code Length
    |--------------------------------------------------------------------------
    */
    'length' => 6,

    /*
    |--------------------------------------------------------------------------
    | Code Expiry (minutes)
    |--------------------------------------------------------------------------
    */
    'expires_minutes' => (int) env('VERIFY_CODE_EXPIRES_MINUTES', 1),

    /*
    |--------------------------------------------------------------------------
    | Fixed Test Code
    |--------------------------------------------------------------------------
    |
    | When set, every generated code uses this value (e.g. 123456 for testing).
    | Set VERIFY_CODE_FIXED=null in production to use random codes.
    |
    */
    'fixed_code' => env('VERIFY_CODE_FIXED', '123456'),

];
