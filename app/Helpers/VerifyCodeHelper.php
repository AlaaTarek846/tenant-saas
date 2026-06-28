<?php

if (! function_exists('generateVerifyCode')) {
    /**
     * Generate a 6-digit verification code.
     * Uses fixed test code when configured (default: 123456).
     */
    function generateVerifyCode(): string
    {
        $fixedCode = config('verify.fixed_code');

        if (filled($fixedCode)) {
            return str_pad((string) $fixedCode, 6, '0', STR_PAD_LEFT);
        }

        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
