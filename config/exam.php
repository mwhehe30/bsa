<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Exam Violation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the system handles exam violations and student monitoring.
    |
    */

    'violations' => [
        // Debounce window in milliseconds to prevent duplicate violation records
        // from race conditions (e.g., browser back button sending multiple requests)
        'debounce_ms' => env('EXAM_VIOLATION_DEBOUNCE_MS', 500),

        // Maximum number of fullscreen exit violations before auto-blocking
        'max_fullscreen_exit' => env('EXAM_MAX_FULLSCREEN_EXIT', 3),

        // Auto-block student immediately on tab switch
        'auto_block_on_tab_switch' => env('EXAM_AUTO_BLOCK_TAB_SWITCH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Settings
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for various exam-related endpoints to prevent
    | abuse, spam, and brute force attacks.
    |
    */

    'rate_limits' => [
        // OTP requests per email address
        'otp_per_email' => env('EXAM_OTP_PER_EMAIL', 3),

        // OTP requests per IP address
        'otp_per_ip' => env('EXAM_OTP_PER_IP', 10),

        // OTP rate limit window in seconds
        'otp_window_seconds' => env('EXAM_OTP_WINDOW_SECONDS', 300),

        // Login attempts before rate limiting kicks in
        'login_attempts' => env('EXAM_LOGIN_ATTEMPTS', 5),

        // Login rate limit window in minutes
        'login_window_minutes' => env('EXAM_LOGIN_WINDOW_MINUTES', 15),

        // Violation logging requests per student (prevent spam)
        'violation_per_student' => env('EXAM_VIOLATION_PER_STUDENT', 20),

        // Violation logging window in seconds
        'violation_window_seconds' => env('EXAM_VIOLATION_WINDOW_SECONDS', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    |
    | Configure OTP (One-Time Password) generation and validation.
    |
    */

    'otp' => [
        // OTP length (number of digits)
        'length' => env('EXAM_OTP_LENGTH', 6),

        // OTP expiry time in minutes
        'expiry_minutes' => env('EXAM_OTP_EXPIRY_MINUTES', 5),

        // Hash OTP before storing in database (recommended for security)
        'hash_storage' => env('EXAM_OTP_HASH_STORAGE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure exam security features and restrictions.
    |
    */

    'security' => [
        // Enforce fullscreen mode during exam
        'require_fullscreen' => env('EXAM_REQUIRE_FULLSCREEN', true),

        // Disable right-click context menu during exam
        'disable_right_click' => env('EXAM_DISABLE_RIGHT_CLICK', true),

        // Disable copy-paste during exam
        'disable_copy_paste' => env('EXAM_DISABLE_COPY_PASTE', true),

        // Enable browser tab visibility monitoring
        'monitor_tab_visibility' => env('EXAM_MONITOR_TAB_VISIBILITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Duration Settings
    |--------------------------------------------------------------------------
    |
    | Configure how exam duration is handled.
    |
    */

    'duration' => [
        // Store duration on server side (recommended to prevent client manipulation)
        'server_side_tracking' => env('EXAM_SERVER_SIDE_DURATION', true),

        // Update interval in seconds (how often client syncs with server)
        'update_interval_seconds' => env('EXAM_DURATION_UPDATE_INTERVAL', 30),

        // Grace period in seconds before auto-submit when time runs out
        'grace_period_seconds' => env('EXAM_GRACE_PERIOD_SECONDS', 5),
    ],

];
